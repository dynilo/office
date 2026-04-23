<?php

use App\Models\Role;
use App\Support\Auth\AuthAccessValidation;

it('reports production auth and role protection as ready when admin surfaces are protected consistently', function (): void {
    $report = app(AuthAccessValidation::class)->report();

    expect($report['ready'])->toBeTrue()
        ->and($report['auth']['default_guard'])->toBe('web')
        ->and($report['checks']['admin_web_routes_require_auth'])->toBeTrue()
        ->and($report['checks']['admin_web_routes_require_role'])->toBeTrue()
        ->and($report['checks']['admin_api_routes_require_auth'])->toBeTrue()
        ->and($report['checks']['admin_api_routes_require_role'])->toBeTrue()
        ->and($report['allowed_admin_roles'])->toBe([
            Role::SUPER_ADMIN,
            Role::ADMIN,
            Role::OPERATOR,
            Role::OBSERVER,
        ]);
});
