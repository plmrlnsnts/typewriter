<?php

namespace Plmrlnsnts\Typewriter;

use Nette\PhpGenerator\PhpNamespace;

class PhpClass extends PhpFile
{
    /** @param list<PhpClassProperty> $properties */
    public function __construct(
        public string $name,
        public ?string $extends,
        public string $namespace,
        public string $path,
        public array $properties
    ) {
        //
    }

    public function render(): string
    {
        $namespace = new PhpNamespace($this->namespace);
        $class = $namespace->addClass($this->name);

        if ($this->extends) {
            $namespace->addUse($this->extends);
            $class->setExtends($this->extends);
        }

        $constructor = $class->addMethod('__construct');
        $constructor->addBody('//');
        $comments = [];

        foreach ($this->properties as $property) {
            $parameter = $constructor->addPromotedParameter($property->name);
            $parameter->setType($property->hint());

            if ($property->useable()) {
                $namespace->addUse($property->cast());
            }

            $type = array_last(explode('\\', $property->cast()));

            if ($property->list) {
                $comments[] = "@param list<{$type}> \${$property->name} {$property->description}";
            } else if ($property->description) {
                $comments[] = "@param {$type} \${$property->name} {$property->description}";
            }

            if ($property->deprecated) {
                $parameter->addAttribute('Deprecated', array_filter(['message' => $property->deprecationReason]));
                $namespace->addUse('Deprecated');
            }
        }

        if ($comments) {
            $constructor->setComment(implode(PHP_EOL, $comments));
        }

        return (string) '<?php'.PHP_EOL.PHP_EOL.$namespace;
    }
}
