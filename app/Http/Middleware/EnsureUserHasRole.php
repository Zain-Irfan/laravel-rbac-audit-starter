<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: ->middleware('role:admin') or ->middleware('role:admin,manager').
 *
 * Prefer the built-in `can:<permission>` middleware for most routes; checking
 * roles directly is for the rare case where the role itself is the rule.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_unless($request->user()?->hasRole($roles), Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
