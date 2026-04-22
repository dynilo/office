<?php

namespace App\Support\Exceptions;

use RuntimeException;

class EntityNotFoundException extends RuntimeException
{
    public static function for(string $entity, string $identifier): self
    {
        return new self(sprintf('%s [%s] was not found.', $entity, $identifier));
    }
}
