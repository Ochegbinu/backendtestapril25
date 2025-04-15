<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $userRole = $request->user()->role;

        if (in_array($userRole, $roles)) {
            return $next($request);
        }

        return response()->json(['message' => 'Access denied. Insufficient permissions.'], 403);
    }
}