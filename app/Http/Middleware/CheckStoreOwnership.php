<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Store;

class CheckStoreOwnership
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        
        // Se for admin, permite acesso a todas as lojas
        if ($user->isAdmin()) {
            return $next($request);
        }

        $storeId = $request->route('store') ?? $request->route('id');
        
        if ($storeId) {
            $store = Store::find($storeId);
            
            if (!$store) {
                return response()->json(['message' => 'Loja não encontrada'], 404);
            }

            if ($store->owner_id !== $user->id) {
                return response()->json(['message' => 'Não tem permissão para aceder esta loja'], 403);
            }
        }

        return $next($request);
    }
}