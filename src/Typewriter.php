<?php

namespace Plmrlnsnts\Typewriter;

use Generator;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\AST\VariableDefinitionNode;
use GraphQL\Language\Parser;
use GraphQL\Language\Visitor;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectField;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\WrappingType;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildClientSchema;
use GraphQL\Utils\TypeInfo;

class Typewriter
{
    protected Schema $schema;

    /** @param list<Entrypoint> $entrypoints */
    public function __construct(
        public string $source,
        public SharedDirectory $enums,
        public SharedDirectory $inputs,
        public array $entrypoints = [],
        public string $typeClass = 'Plmrlnsnts\Typewriter\Type',
    ) {
        $contents = file_get_contents($this->source);
        $json = json_decode($contents, true);
        $this->schema = BuildClientSchema::build($json);
    }

    public function addEntrypoint(Entrypoint $entrypoint): static
    {
        $this->entrypoints[] = $entrypoint;

        return $this;
    }

    public function addCast(string $graphqlType, string $phpType): static
    {
        PhpClassProperty::$casts[$graphqlType] = $phpType;

        return $this;
    }

    public function generate(): void
    {
        $this->setup();

        $sources = Filesystem::sources($this->entrypoints);

        foreach ($sources as $source) {
            $stream = $this->process($source);

            foreach ($stream as $output) {
                Filesystem::store($output->path, $output->render());
            }
        }
    }

    /** @return Generator<int, PhpFile> */
    protected function process(Source $source): Generator
    {
        $ast = Parser::parse($source->content);
        $typeinfo = new TypeInfo($this->schema);

        $operation = [];
        $fields = [];
        $stack = [];
        $enums = [];
        $fragments = [];
        $variables = [];

        Visitor::visit($ast, Visitor::visitWithTypeInfo($typeinfo, [
            NodeKind::OPERATION_DEFINITION => [
                'enter' => function (OperationDefinitionNode $node) use (&$operation, $typeinfo, $source) {
                    $type = $typeinfo->getType();
                    $operation['name'] = $node->name ? $node->name->value : $source->name;
                    $operation['type'] = $type instanceof NamedType ? $type->name : null;
                },
            ],
            NodeKind::INLINE_FRAGMENT => [
                'enter' => function (InlineFragmentNode $node) use (&$fragments) {
                    $fragments[] = $node->typeCondition->name->value;
                },
                'leave' => function () use (&$fragments) {
                    array_pop($fragments);
                },
            ],
            NodeKind::FIELD => [
                'enter' => function (FieldNode $node) use ($typeinfo, &$fields, &$stack, &$fragments, $source) {
                    $name = $node->alias
                        ? $node->alias->value
                        : $node->name->value;

                    $field['name'] = $name;
                    $field['parent'] = implode('.', $stack);

                    $type = $typeinfo->getType();
                    $defs = $typeinfo->getFieldDef();

                    if (empty($type)) {
                        throw new InvalidField($source, $node);
                    }

                    $details = $this->unwrap($type, $defs);

                    // Enforce all fields defined inside an inline fragment to be nullable.
                    // eg. { ...on Product { id, name } }

                    $details['nullable'] = empty($fragments)
                        ? $details['nullable']
                        : true;

                    $field = array_merge($field, $details);

                    $fields[] = $field;
                    $stack[] = $name;
                },
                'leave' => function () use (&$stack) {
                    array_pop($stack);
                },
            ],
            NodeKind::VARIABLE_DEFINITION => [
                'enter' => function (VariableDefinitionNode $node) use ($typeinfo, &$variables) {
                    $name = $node->variable->name->value;

                    $type = $typeinfo->getInputType();
                    $defs = $typeinfo->getFieldDef();
                    $details = $this->unwrap($type, $defs);

                    $variable['name'] = $name;
                    $variable = array_merge($variable, $details);

                    $variables[] = $variable;
                },
            ],
        ]));

        $fieldgroup = array_reduce($fields, function ($result, $current) {
            $parent = $current['parent'];
            unset($current['parent']);

            $result[$parent] ??= [];
            $result[$parent][$current['name']] = $current;

            return $result;
        }, []);

        foreach ($fieldgroup as $name => $fields) {
            $classname = empty($name)
                ? cn($operation['name'].'Result')
                : cn($name);

            $properties = [];

            foreach ($fields as $field) {
                $property = new PhpClassProperty(
                    name: $field['name'],
                    type: match (true) {
                        $field['scalar'] => $field['type'],
                        $field['enum'] => $this->enums->namespace.'\\'.$field['type'],
                        default => implode('\\', [
                            $source->entrypoint->namespace,
                            $operation['name'],
                            cn($name.'.'.$field['name']),
                        ]),
                    },
                    nullable: $field['nullable'],
                    list: $field['list'],
                    scalar: $field['scalar'],
                    description: $field['description'],
                    deprecated: $field['deprecated'],
                    deprecationReason: $field['deprecationReason'],
                );

                $properties[] = $property;

                if ($field['enum']) {
                    $enums[$field['type']] = $field['type'];
                }
            }

            yield new PhpClass(
                name: $classname,
                extends: $this->typeClass,
                namespace: $source->entrypoint->namespace.'\\'.cn($operation['name']),
                path: $source->entrypoint->output.'/'.cn($operation['name']).'/'.$classname.'.php',
                properties: $properties,
            );
        }

        $cache = [];

        $inputs = function (InputObjectType $type) use (&$inputs, &$cache, &$enums) {
            if (isset($cache[$type->name])) {
                return; // prevent infinite loop in case one of the input objects have a circular dependency
            } else {
                $cache[$type->name] = $type->name;
            }

            $properties = [];

            foreach ($type->getFields() as $field) {
                $details = $this->unwrap($field->getType(), $field);

                $property = new PhpClassProperty(
                    name: $field->name,
                    type: match (true) {
                        $details['scalar'] => $details['type'],
                        $details['enum'] => $this->enums->namespace.'\\'.$details['type'],
                        default => $this->inputs->namespace.'\\'.cn($details['type']),
                    },
                    nullable: $details['nullable'],
                    list: $details['list'],
                    scalar: $details['scalar'],
                    description: $details['description'],
                    deprecated: $details['deprecated'],
                    deprecationReason: $details['deprecationReason'],
                );

                $properties[] = $property;

                if ($details['enum']) {
                    $enums[$details['type']] = $details['type'];
                }

                $fieldtype = $field->getType();
                $innertype = $fieldtype instanceof WrappingType
                    ? $fieldtype->getInnermostType()
                    : $fieldtype;

                if ($innertype instanceof InputObjectType) {
                    yield from $inputs($innertype);
                }
            }

            yield new PhpClass(
                name: cn($type->name),
                namespace: $this->inputs->namespace,
                path: $this->inputs->directory.'/'.cn($type->name).'.php',
                properties: $properties,
                extends: null
            );
        };

        foreach ($variables as $variable) {
            $type = $this->schema->getType($variable['type']);

            if ($type instanceof InputObjectType) {
                yield from $inputs($type);
            }
        }

        if (count($variables) > 0) {
            $classname = cn($operation['name'].'Variables');
            $properties = [];

            foreach ($variables as $variable) {
                $property = new PhpClassProperty(
                    name: $variable['name'],
                    type: match (true) {
                        $variable['scalar'] => $variable['type'],
                        $variable['enum'] => $this->enums->namespace.'\\'.$variable['type'],
                        default => $this->inputs->namespace.'\\'.cn($variable['type']),
                    },
                    scalar: $variable['scalar'],
                    nullable: $variable['nullable'],
                    list: $variable['list'],
                    description: $variable['description'],
                    deprecated: $variable['deprecated'],
                    deprecationReason: $variable['deprecationReason'],
                );

                $properties[] = $property;

                if ($variable['enum']) {
                    $enums[$variable['type']] = $variable['type'];
                }
            }

            yield new PhpClass(
                name: $classname,
                namespace: $source->entrypoint->namespace.'\\'.cn($operation['name']),
                extends: $this->typeClass,
                path: $source->entrypoint->output.'/'.cn($operation['name']).'/'.$classname.'.php',
                properties: $properties,
            );
        }

        foreach ($enums as $enum) {
            $type = $this->schema->getType($enum);

            if ($type instanceof EnumType) {
                $cases = [];

                foreach ($type->getValues() as $value) {
                    $cases[] = new PhpEnumCase(
                        name: $value->name,
                        value: $value->value,
                        description: $value->description,
                        deprecated: $value->isDeprecated(),
                        deprecationReason: $value->deprecationReason,
                    );
                }

                yield new PhpEnum(
                    name: cn($enum),
                    namespace: $this->enums->namespace,
                    path: $this->enums->directory.'/'.cn($enum).'.php',
                    cases: $cases,
                );
            }
        }
    }

    protected function unwrap(Type $type, null|FieldDefinition|InputObjectField $field): array
    {
        $list = false;
        $nullable = true;

        while ($type instanceof WrappingType) {
            if ($type instanceof ListOfType) {
                $list = true;
            }

            if ($type instanceof NonNull) {
                $nullable = false;
            }

            $type = $type->getWrappedType();
        }

        return [
            'type' => $type instanceof NamedType ? $type->name : null,
            'list' => $list,
            'nullable' => $nullable,
            'scalar' => $type instanceof ScalarType,
            'enum' => $type instanceof EnumType,
            'description' => $field?->description
                ? trim($field->description)
                : null,
            'deprecated' => $field?->isDeprecated() || false,
            'deprecationReason' => $field?->deprecationReason
                ? trim($field->deprecationReason)
                : null,
        ];
    }

    protected function setup(): void
    {
        Filesystem::empty($this->enums->directory);

        Filesystem::empty($this->inputs->directory);

        foreach ($this->entrypoints as $entrypoint) {
            Filesystem::empty($entrypoint->output);
        }
    }
}
