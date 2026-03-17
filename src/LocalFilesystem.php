<?php

namespace Plmrlnsnts\Typewriter;

use Generator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class LocalFilesystem implements FilesystemInterface
{
    public function sources(array $entrypoints): Generator
    {
        foreach ($entrypoints as $entrypoint) {
            $files = $this->scan($entrypoint->input, '/(.graphql|.gql)$/i');

            foreach ($files as $file) {
                if ($file->isFile()) {
                    yield new Source(
                        entrypoint: $entrypoint,
                        name: pathinfo($file->getPathname(), PATHINFO_FILENAME),
                        content: file_get_contents($file->getPathname()),
                    );
                }
            }
        }
    }

    public function store(string $path, string $contents): void
    {
        $directory = dirname($path);

        if ($this->missing($directory)) {
            mkdir($directory, recursive: true);
        }

        file_put_contents($path, $contents);
    }

    public function empty(string $path): void
    {
        if ($this->missing($path)) {
            return;
        }

        $files = $this->scan($path, mode: RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($path);
    }

    protected function exists($path): bool
    {
        return file_exists($path);
    }

    protected function missing($path): bool
    {
        return ! $this->exists($path);
    }

    /**
     * @param  callable(\SplFileInfo): bool  $filter
     * @return Generator<int, \SplFileInfo>
     */
    public function scan(string $path, ?string $pattern = null, ?int $mode = null): Generator
    {
        $iterator = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);

        /** @var RecursiveIteratorIterator<int, \SplFileInfo> */
        $files = new RecursiveIteratorIterator($iterator, $mode ?? RecursiveIteratorIterator::LEAVES_ONLY);

        foreach ($files as $file) {
            if (empty($pattern) || preg_match($pattern, $file->getPathname())) {
                yield $file;
            }
        }
    }
}
