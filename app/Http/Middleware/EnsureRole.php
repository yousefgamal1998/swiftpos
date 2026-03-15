<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $normalizedRoles = collect($roles)
            ->flatMap(fn (string $role) => explode('|', $role))
            ->filter()
            ->values()
            ->all();

        if (! empty($normalizedRoles) && ! $user->hasAnyRole($normalizedRoles)) {
            abort(403, 'You do not have permission to access this area.');
        }

        return $next($request);
    }
}
