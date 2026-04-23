<?php

namespace App\Application\Memory\Exceptions;

use RuntimeException;

class EmbeddingProviderException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message,
        public readonly string $provider,
        public readonly ?int $statusCode = null,
        public readonly ?string $errorCode = null,
        public readonly bool $retriable = false,
        public readonly array $context = [],
    ) {
        parent::__construct($message);
    }

    public static function transport(string $provider, string $message): self
    {
        return new self(
            message: $message,
            provider: $provider,
            statusCode: null,
            errorCode: 'transport_error',
            retriable: true,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function response(
        string $provider,
        string $message,
        ?int $statusCode,
        ?string $errorCode,
        bool $retriable,
        array $context = [],
    ): self {
        return new self(
            message: $message,
            provider: $provider,
            statusCode: $statusCode,
            errorCode: $errorCode,
            retriable: $retriable,
            context: $context,
        );
    }
}
