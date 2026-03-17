<?php

namespace Plmrlnsnts\Typewriter;

use Generator;
use PHPUnit\Framework\Assert;

class ArrayFilesystem implements FilesystemInterface
{
    /** @var array<string, string> */
    protected array $outputs = [];

    /** @param  list<Source>  $sources*/
    public function __construct(protected array $sources)
    {
        //
    }

    public function sources(array $entrypoints): Generator
    {
        foreach ($this->sources as $source) {
            yield $source;
        }
    }

    public function store(string $path, string $content): void
    {
        $this->outputs[$path] = $content;
    }

    public function empty(string $path): void
    {
        //
    }

    public function assertHasOutput(string $expected): void
    {
        $found = false;

        foreach (array_keys($this->outputs) as $path) {
            $actual = pathinfo($path, PATHINFO_FILENAME);

            if ($actual === $expected) {
                $found = true;
                break;
            }
        }

        Assert::assertTrue($found, sprintf('Output "%s" does not exist.', $expected));
    }
}
