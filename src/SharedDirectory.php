<?php

namespace Plmrlnsnts\Typewriter;

class SharedDirectory
{
    public function __construct(
        public string $directory,
        public string $namespace
    )
    {
        //
    }
}
