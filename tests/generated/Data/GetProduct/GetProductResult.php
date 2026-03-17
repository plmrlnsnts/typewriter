<?php

namespace Plmrlnsnts\TypewriterApp\Data\GetProduct;

use Plmrlnsnts\Typewriter\Type;

class GetProductResult extends Type
{
	/**
	 * @param Product $product Fetch a specific `Product` by one of its unique attributes.
	 */
	public function __construct(
		public ?Product $product,
	) {
		//
	}
}
