<?php

namespace App\Support\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;

final readonly class AuthAccessValidation
{
    public function __construct(
        private Router $router,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        $routes = collect($this->router->getRoutes()->getRoutes());
        $adminWebRoutes = $routes->filter(fn (Route $route): bool => str_starts_with($route->uri(), 'admin'));
        $adminApiRoutes = $routes->filter(fn (Route $route): bool => str_starts_with($route->uri(), 'api/admin'));

        $checks = [
            'default_guard_is_web' => (string) config('auth.defaults.guard', 'web') === 'web',
            'web_guard_uses_session_driver' => (string) config('auth.guards.web.driver', '') === 'session',
            'users_provider_is_eloquent' => (string) config('auth.providers.users.driver', '') === 'eloquent',
            'user_model_supports_roles' => method_exists(User::class, 'hasRole'),
            'user_model_supports_permissions' => method_exists(User::class, 'hasPermission'),
            'role_middleware_available' => $adminWebRoutes->isNotEmpty()
                && $adminApiRoutes->isNotEmpty()
                && $adminWebRoutes->every(fn (Route $route): bool => $this->routeHasMiddlewarePrefix($route, 'role:'))
                && $adminApiRoutes->every(fn (Route $route): bool => $this->routeHasMiddlewarePrefix($route, 'role:')),
            'login_route_present' => $this->routeNamed('login') !== null,
            'logout_route_present' => $this->routeNamed('logout') !== null,
            'admin_web_routes_present' => $adminWebRoutes->isNotEmpty(),
            'admin_web_routes_require_auth' => $adminWebRoutes->every(fn (Route $route): bool => $this->routeHasMiddleware($route, 'auth')),
            'admin_web_routes_require_role' => $adminWebRoutes->every(fn (Route $route): bool => $this->routeHasMiddlewarePrefix($route, 'role:')),
            'admin_api_routes_present' => $adminApiRoutes->isNotEmpty(),
            'admin_api_routes_require_auth' => $adminApiRoutes->every(fn (Route $route): bool => $this->routeHasMiddleware($route, 'auth')),
            'admin_api_routes_require_role' => $adminApiRoutes->every(fn (Route $route): bool => $this->routeHasMiddlewarePrefix($route, 'role:')),
            'allowed_admin_roles_complete' => $this->allowedRolesAreExpected($adminWebRoutes, $adminApiRoutes),
        ];

        return [
            'environment' => (string) config('app.env'),
            'auth' => [
                'default_guard' => (string) config('auth.defaults.guard', 'web'),
                'web_guard_driver' => (string) config('auth.guards.web.driver', ''),
                'users_provider_driver' => (string) config('auth.providers.users.driver', ''),
            ],
            'protected_surfaces' => [
                'admin_web' => $adminWebRoutes->map(fn (Route $route): array => [
                    'uri' => $route->uri(),
                    'name' => $route->getName(),
                    'middleware' => $route->gatherMiddleware(),
                ])->values()->all(),
                'admin_api' => $adminApiRoutes->map(fn (Route $route): array => [
                    'uri' => $route->uri(),
                    'name' => $route->getName(),
                    'middleware' => $route->gatherMiddleware(),
                ])->values()->all(),
            ],
            'allowed_admin_roles' => [
                Role::SUPER_ADMIN,
                Role::ADMIN,
                Role::OPERATOR,
                Role::OBSERVER,
            ],
            'checks' => $checks,
            'ready' => ! in_array(false, $checks, true),
            'unavailable_reason' => $this->firstFailedCheck($checks),
        ];
    }

    private function routeNamed(string $name): ?Route
    {
        return collect($this->router->getRoutes()->getRoutes())
            ->first(fn (Route $route): bool => $route->getName() === $name);
    }

    private function routeHasMiddleware(Route $route, string $middleware): bool
    {
        return in_array($middleware, $route->gatherMiddleware(), true);
    }

    private function routeHasMiddlewarePrefix(Route $route, string $prefix): bool
    {
        return collect($route->gatherMiddleware())
            ->contains(static fn (string $middleware): bool => str_starts_with($middleware, $prefix));
    }

    /**
     * @param  Collection<int, Route>  $adminWebRoutes
     * @param  Collection<int, Route>  $adminApiRoutes
     */
    private function allowedRolesAreExpected($adminWebRoutes, $adminApiRoutes): bool
    {
        $expected = implode(',', [
            Role::SUPER_ADMIN,
            Role::ADMIN,
            Role::OPERATOR,
            Role::OBSERVER,
        ]);

        $matches = static fn (Route $route): bool => collect($route->gatherMiddleware())
            ->contains(static fn (string $middleware): bool => $middleware === 'role:'.$expected);

        return $adminWebRoutes->every($matches) && $adminApiRoutes->every($matches);
    }

    /**
     * @param  array<string, bool>  $checks
     */
    private function firstFailedCheck(array $checks): ?string
    {
        foreach ($checks as $check => $passed) {
            if (! $passed) {
                return $check;
            }
        }

        return null;
    }
}
