<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        // Autorização para ver lista de empresas
        $this->authorize('viewAny', Company::class);

        $query = Company::with(['mainBranch', 'users', 'stores']);

        // Para sellers, mostrar apenas empresas onde estão associados
        if ($request->user()->isSeller() && !$request->user()->isAdmin()) {
            $query->whereHas('users', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            });
        }

        // Filtros (mantém os existentes)
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nif', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $companies = $query->orderBy('created_at', 'desc')
                          ->paginate($request->get('per_page', 15));

        return response()->json($companies);
    }

    public function show($id)
    {
        $company = Company::with([
            'branches',
            'stores.owner',
            'users',
            'warehouses',
            'drivers.user'
        ])->findOrFail($id);

        // Autorização para ver empresa específica
        $this->authorize('view', $company);

        return response()->json($company);
    }

    public function store(Request $request)
    {
        // Autorização para criar empresas
        $this->authorize('create', Company::class);

        $request->validate([
            'name' => 'required|string|max:255',
            'nif' => 'required|string|max:25|unique:companies',
            'email' => 'required|email',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'type' => 'required|in:individual,collective',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->all();

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('companies/logo', 'public');
        }

        $company = Company::create($data);

        // Associar o utilizador atual como owner se for seller
        if ($request->user()->isSeller()) {
            $company->users()->attach($request->user()->id, [
                'role' => 'owner',
                'is_active' => true
            ]);
        }

        return response()->json([
            'message' => 'Empresa criada com sucesso',
            'company' => $company
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $company = Company::findOrFail($id);

        // Autorização para atualizar empresa
        $this->authorize('update', $company);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email',
            'phone' => 'sometimes|required|string|max:20',
            'address' => 'sometimes|required|string',
            'city' => 'sometimes|required|string|max:100',
            'postal_code' => 'sometimes|required|string|max:20',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
        ]);

        $data = $request->all();

        if ($request->hasFile('logo')) {
            // Remove logo antiga
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $data['logo'] = $request->file('logo')->store('companies/logo', 'public');
        }

        $company->update($data);

        return response()->json([
            'message' => 'Empresa atualizada com sucesso',
            'company' => $company->fresh()
        ]);
    }

    public function addUser(Request $request, $companyId)
    {
        $company = Company::findOrFail($companyId);

        // Autorização para gerir utilizadores da empresa
        $this->authorize('manageUsers', $company);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:owner,manager,employee',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        // Verificar se o utilizador já pertence à empresa
        if ($company->users()->where('user_id', $request->user_id)->exists()) {
            return response()->json([
                'message' => 'O utilizador já pertence a esta empresa'
            ], 422);
        }

        // Verificar permissão para atribuir role de owner
        if ($request->role === 'owner' && !Gate::allows('is-company-owner', $company)) {
            return response()->json([
                'message' => 'Apenas o proprietário atual pode atribuir a role de owner'
            ], 403);
        }

        $company->users()->attach($request->user_id, [
            'role' => $request->role,
            'branch_id' => $request->branch_id,
            'is_active' => true
        ]);

        return response()->json([
            'message' => 'Utilizador adicionado à empresa com sucesso'
        ]);
    }

    public function removeUser($companyId, $userId)
    {
        $company = Company::findOrFail($companyId);

        // Autorização para gerir utilizadores da empresa
        $this->authorize('manageUsers', $company);

        // Não permitir remover o último owner
        $userToRemove = $company->users()->where('user_id', $userId)->first();
        
        if ($userToRemove && $userToRemove->pivot->role === 'owner') {
            $ownerCount = $company->users()->wherePivot('role', 'owner')->count();
            if ($ownerCount <= 1) {
                return response()->json([
                    'message' => 'Não é possível remover o único proprietário da empresa'
                ], 422);
            }
        }

        // Não permitir que um utilizador se remova a si mesmo
        if ($userId == auth()->id()) {
            return response()->json([
                'message' => 'Não pode remover-se a si mesmo da empresa'
            ], 422);
        }

        $company->users()->detach($userId);

        return response()->json([
            'message' => 'Utilizador removido da empresa com sucesso'
        ]);
    }

    public function toggleStatus($id)
    {
        $company = Company::findOrFail($id);

        // Autorização para ativar/desativar empresa
        $this->authorize('toggleStatus', $company);

        $company->update([
            'is_active' => !$company->is_active
        ]);

        return response()->json([
            'message' => 'Estado da empresa atualizado',
            'company' => $company->fresh()
        ]);
    }

    public function destroy($id)
    {
        $company = Company::findOrFail($id);

        // Autorização para eliminar empresa
        $this->authorize('delete', $company);

        $company->delete();

        return response()->json([
            'message' => 'Empresa eliminada com sucesso'
        ]);
    }

    public function getCompanyStats($id)
    {
        $company = Company::findOrFail($id);

        // Autorização para ver relatórios da empresa
        $this->authorize('viewReports', $company);

        $stats = [
            'total_stores' => $company->stores()->count(),
            'total_branches' => $company->branches()->count(),
            'total_warehouses' => $company->warehouses()->count(),
            'total_employees' => $company->users()->count(),
            'active_stores' => $company->stores()->where('is_active', true)->count(),
            'total_products' => $company->stores()->withCount('products')->get()->sum('products_count'),
        ];

        return response()->json($stats);
    }
}