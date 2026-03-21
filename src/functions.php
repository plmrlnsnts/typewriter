<?php

namespace Plmrlnsnts\Typewriter;

function cn(string $string): string
{
    $string = preg_replace('/[^a-z0-9\s]/i', ' ', $string);
    $string = ucwords($string);
    $string = str_replace(' ', '', $string);

    return $string;
}

if (! function_exists('array_last')) {
    /**
     * Polyfill for array_last (PHP < 8.5)
     *
     * @param  mixed  $default
     */
    function array_last(array $array): mixed
    {
        return $array === [] ? null : $array[array_key_last($array)];
    }
}
