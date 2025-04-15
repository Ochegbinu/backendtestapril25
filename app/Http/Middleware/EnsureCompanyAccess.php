<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureCompanyAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        
        if ($request->route('expense')) {
            $expense = $request->route('expense');
            if ($expense->company_id !== $user->company_id) {
                return response()->json(['message' => 'Unauthorized access to resource.'], 403);
            }
        }
        
        if ($request->route('user')) {
            $requestedUser = $request->route('user');
            if ($requestedUser->company_id !== $user->company_id) {
                return response()->json(['message' => 'Unauthorized access to resource.'], 403);
            }
        }
        
        return $next($request);
    }
}