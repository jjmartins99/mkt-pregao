<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Company;

class CheckCompanyAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        
        if ($user->isAdmin()) {
            return $next($request);
        }

        $companyId = $request->route('company') ?? $request->route('id');
        
        if ($companyId) {
            $company = Company::find($companyId);
            
            if (!$company) {
                return response()->json(['message' => 'Empresa não encontrada'], 404);
            }

            if (!$company->users()->where('user_id', $user->id)->exists()) {
                return response()->json(['message' => 'Não tem permissão para aceder esta empresa'], 403);
            }
        }

        return $next($request);
    }
}