<?php

namespace Plmrlnsnts\Typewriter;

class Entrypoint
{
    public function __construct(
        public string $input,
        public string $output,
        public string $namespace
    ) {
        //
    }
}
