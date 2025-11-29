<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Company;

class CheckCompanyAccess
{
    public function handle(Request $request, Closure $next)
    {
        $companyId = $request->route('company') ?? $request->route('id');
        
        if ($companyId) {
            $company = Company::find($companyId);
            
            if (!$company) {
                return response()->json(['message' => 'Empresa não encontrada'], 404);
            }

            // Usar a policy em vez de lógica customizada
            if (!auth()->user()->can('view', $company)) {
                return response()->json(['message' => 'Não tem permissão para aceder esta empresa'], 403);
            }
        }

        return $next($request);
    }
}