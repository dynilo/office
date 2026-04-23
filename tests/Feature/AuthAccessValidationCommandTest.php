<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

it('exposes auth and access runtime validation through a console command', function (): void {
    expect(Artisan::call('auth:validate-runtime'))->toBe(0);

    $output = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

    expect($output['ready'])->toBeTrue()
        ->and($output['checks']['login_route_present'])->toBeTrue()
        ->and($output['checks']['admin_api_routes_require_role'])->toBeTrue();
});

it('documents production auth and access expectations', function (): void {
    $document = File::get(base_path('docs/AUTH_ACCESS_PRODUCTION.md'));

    expect($document)->toContain('php artisan auth:validate-runtime')
        ->toContain('/api/admin/*')
        ->toContain('Allowed admin roles');
});
