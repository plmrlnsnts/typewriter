<?php

namespace Plmrlnsnts\Typewriter;

use Stringable;

abstract class PhpFile implements Stringable
{
    public string $path;

    abstract public function render(): string;

    public function __toString(): string
    {
        return $this->render();
    }
}
