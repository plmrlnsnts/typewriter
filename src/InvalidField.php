<?php

namespace Plmrlnsnts\Typewriter;

use GraphQL\Language\AST\FieldNode;
use RuntimeException;

class InvalidField extends RuntimeException
{
    public function __construct(Source $source, FieldNode $node)
    {
        $message = sprintf(
            'The source [%s] contains an invalid field [%s].',
            $source->name,
            $node->alias ? $node->alias->value : $node->name->value
        );

        parent::__construct($message);
    }
}
