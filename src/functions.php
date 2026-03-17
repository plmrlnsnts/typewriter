<?php

namespace Plmrlnsnts\Typewriter;

function cn(string $string): string
{
    $string = preg_replace('/[^a-z0-9\s]/i', ' ', $string);
    $string = ucwords($string);
    $string = str_replace(' ', '', $string);

    return $string;
}
