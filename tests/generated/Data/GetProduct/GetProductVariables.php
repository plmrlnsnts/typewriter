<?php

namespace Plmrlnsnts\TypewriterApp\Data\GetProduct;

use Plmrlnsnts\Typewriter\Type;

class GetProductVariables extends Type
{
    public function __construct(
        public string $handle,
    ) {
        //
    }
}
