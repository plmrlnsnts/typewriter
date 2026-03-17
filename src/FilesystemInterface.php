<?php

namespace Plmrlnsnts\Typewriter;

use Generator;

interface FilesystemInterface
{
    /**
     * @param  list<Entrypoint>  $entrypoints
     * @return Generator<int, Source>
     */
    public function sources(array $entrypoints): Generator;

    public function store(string $path, string $content): void;

    public function empty(string $path): void;
}
