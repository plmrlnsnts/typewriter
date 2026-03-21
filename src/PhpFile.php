<?php

namespace Plmrlnsnts\Typewriter;

use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;
use Stringable;

abstract class PhpFile implements Stringable
{
    public string $path;

    abstract public function build(): PhpNamespace;

    public function __toString(): string
    {
        $printer = new PsrPrinter;

        return '<?php'.PHP_EOL.PHP_EOL.$printer->printNamespace($this->build());
    }
}
