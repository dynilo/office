<?php

use App\Support\Security\SecretRedactor;

it('recursively redacts sensitive configuration keys and known configured secret values', function (): void {
    config()->set('providers.openai_compatible.api_key', 'sk-secret-config-value');
    config()->set('providers.openai_compatible.organization', 'org-secret-config-value');
    config()->set('providers.openai_compatible.project', 'proj-secret-config-value');

    $redacted = app(SecretRedactor::class)->redactArray([
        'provider' => [
            'api_key' => 'sk-secret-config-value',
            'organization' => 'org-secret-config-value',
            'project' => 'proj-secret-config-value',
            'base_url' => 'https://api.example.test/v1',
            'nested' => [
                'Authorization' => 'Bearer sk-secret-config-value',
                'message' => 'Failed for key sk-secret-config-value in project proj-secret-config-value.',
            ],
        ],
        'safe' => 'visible',
    ]);

    expect($redacted['provider']['api_key'])->toBe('[REDACTED]')
        ->and($redacted['provider']['organization'])->toBe('[REDACTED]')
        ->and($redacted['provider']['project'])->toBe('[REDACTED]')
        ->and($redacted['provider']['base_url'])->toBe('https://api.example.test/v1')
        ->and($redacted['provider']['nested']['Authorization'])->toBe('[REDACTED]')
        ->and($redacted['provider']['nested']['message'])->toBe('Failed for key [REDACTED] in project [REDACTED].')
        ->and($redacted['safe'])->toBe('visible');
});

it('provides a safe provider configuration debug surface', function (): void {
    config()->set('providers.openai_compatible', [
        'base_url' => 'https://api.openai.com/v1',
        'api_key' => 'sk-debug-secret',
        'organization' => 'org-debug-secret',
        'project' => 'proj-debug-secret',
        'model' => 'gpt-5',
        'timeout' => 30,
    ]);

    $safeConfig = app(SecretRedactor::class)->redactArray(config('providers.openai_compatible'));
    $encoded = json_encode($safeConfig, JSON_THROW_ON_ERROR);

    expect($safeConfig['api_key'])->toBe('[REDACTED]')
        ->and($safeConfig['organization'])->toBe('[REDACTED]')
        ->and($safeConfig['project'])->toBe('[REDACTED]')
        ->and($safeConfig['model'])->toBe('gpt-5')
        ->and($encoded)->not->toContain('sk-debug-secret')
        ->and($encoded)->not->toContain('org-debug-secret')
        ->and($encoded)->not->toContain('proj-debug-secret');
});
