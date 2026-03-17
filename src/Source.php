<?php

namespace Plmrlnsnts\Typewriter;

class Source
{
    public function __construct(
        public Entrypoint $entrypoint,
        public string $name,
        public string $content
    ) {
        //
    }
}
