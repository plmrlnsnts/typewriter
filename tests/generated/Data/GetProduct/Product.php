<?php

namespace Plmrlnsnts\TypewriterApp\Data\GetProduct;

use Plmrlnsnts\Typewriter\Type;

class Product extends Type
{
    /**
     * @param string $id A globally-unique ID.
     * @param string $handle A unique, human-readable string of the product's title.
     * A handle can contain letters, hyphens (`-`), and numbers, but no spaces.
     * The handle is used in the online store URL for the product.
     * @param string $title The name for the product that displays to customers. The title is used to construct the product's handle.
     * For example, if a product is titled "Black Sunglasses", then the handle is `black-sunglasses`.
     */
    public function __construct(
        public string $id,
        public string $handle,
        public string $title,
    ) {
        //
    }
}
