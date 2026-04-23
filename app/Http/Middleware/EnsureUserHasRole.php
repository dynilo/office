<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        if ($roles === [] || ! method_exists($user, 'hasRole') || ! $user->hasRole($roles)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
