<?php

namespace App\Support\Exceptions;

use RuntimeException;

class InvalidStateException extends RuntimeException
{
    public static function forTransition(string $entity, string $fromState, string $toState): self
    {
        return new self(sprintf(
            'Invalid state transition for %s from [%s] to [%s].',
            $entity,
            $fromState,
            $toState,
        ));
    }
}
