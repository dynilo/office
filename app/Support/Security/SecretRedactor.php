<?php

namespace App\Support\Security;

final class SecretRedactor
{
    public function replacement(): string
    {
        return (string) config('secrets.redaction.replacement', '[REDACTED]');
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function redactArray(array $values): array
    {
        $redacted = [];

        foreach ($values as $key => $value) {
            $redacted[$key] = $this->redactValue($key, $value);
        }

        return $redacted;
    }

    public function redactString(string $value): string
    {
        $redacted = $value;

        foreach ($this->configuredSecretValues() as $secret) {
            $redacted = str_replace($secret, $this->replacement(), $redacted);
        }

        return $redacted;
    }

    private function redactValue(string|int $key, mixed $value): mixed
    {
        if ($this->isSensitiveKey((string) $key)) {
            return $this->replacement();
        }

        if (is_array($value)) {
            return $this->redactArray($value);
        }

        if (is_string($value)) {
            return $this->redactString($value);
        }

        return $value;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = str($key)->lower()->replace(['_', '-'], '')->toString();

        foreach ($this->keyPatterns() as $pattern) {
            $normalizedPattern = str($pattern)->lower()->replace(['_', '-'], '')->toString();

            if ($normalized === $normalizedPattern || str_contains($normalized, $normalizedPattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function keyPatterns(): array
    {
        return array_values(array_filter(
            config('secrets.redaction.key_patterns', []),
            static fn (mixed $value): bool => is_string($value) && $value !== '',
        ));
    }

    /**
     * @return array<int, string>
     */
    private function configuredSecretValues(): array
    {
        $paths = array_filter(
            config('secrets.redaction.config_paths', []),
            static fn (mixed $value): bool => is_string($value) && $value !== '',
        );

        return array_values(array_filter(
            array_map(static fn (string $path): mixed => config($path), $paths),
            static fn (mixed $value): bool => is_string($value) && $value !== '',
        ));
    }
}
