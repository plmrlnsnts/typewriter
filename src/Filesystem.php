<?php

namespace Plmrlnsnts\Typewriter;

/**
 * @method static \Generator<int, Source> sources(list<Entrypoint> $entrypoints)
 * @method static void store(string $path, string $content)
 * @method static void empty(string $path)
 */
class Filesystem
{
    public static ?FilesystemInterface $instance = null;

    public static function __callStatic($name, $arguments): mixed
    {
        static::$instance ??= new LocalFilesystem;

        return static::$instance->$name(...$arguments);
    }

    /** @param list<Source>|Source $sources */
    public static function fake($sources): ArrayFilesystem
    {
        $sources = is_array($sources) ? $sources : [$sources];

        return static::$instance = new ArrayFilesystem($sources);
    }
}
