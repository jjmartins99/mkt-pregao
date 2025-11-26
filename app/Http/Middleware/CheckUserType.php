<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckUserType
{
    public function handle(Request $request, Closure $next, ...$types)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Não autenticado'], 401);
        }

        if (!in_array($user->type, $types)) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        return $next($request);
    }
}