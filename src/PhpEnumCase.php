<?php

namespace Plmrlnsnts\Typewriter;

class PhpEnumCase
{
    public function __construct(
        public string $name,
        public string $value,
        public ?string $description,
        public bool $deprecated,
        public ?string $deprecationReason,
    ) {
        //
    }
}
