<?php

namespace Plmrlnsnts\Typewriter;

class PhpClassProperty
{
    /** @var array<string, string> */
    public static array $casts = [
        'Integer' => 'int',
        'Int' => 'int',
        'Boolean' => 'bool',
        'Bool' => 'bool',
        'String' => 'string',
    ];

    public function __construct(
        public string $name,
        public string $type,
        public bool $nullable,
        public bool $list,
        public bool $scalar,
        public ?string $description,
        public bool $deprecated,
        public ?string $deprecationReason,
    ) {
        //
    }

    public function cast(): string
    {
        $casted = PhpClassProperty::$casts[$this->type] ?? null;

        return match (true) {
            isset($casted) => $casted,
            $this->scalar => 'string',
            default => $this->type,
        };
    }

    public function hint(): string
    {
        $type = match (true) {
            $this->list => 'array',
            default => $this->cast(),
        };

        return $this->nullable
            ? "?{$type}"
            : $type;
    }

    public function useable(): bool
    {
        return ! in_array($this->cast(), ['string', 'int', 'bool', 'float']);
    }
}
