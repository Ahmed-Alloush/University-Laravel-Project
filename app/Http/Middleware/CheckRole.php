<?php

namespace App\Http\Middleware;




use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed  ...$roles
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        // Ensure the user is authenticated and their role matches one of the allowed roles
        if (!$user || !in_array(strtolower($user->role), array_map('strtolower', $roles))) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access. You do not have the required permissions.',
            ], 403);
        }

        return $next($request);
    }
}
