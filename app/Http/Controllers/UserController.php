<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['stores', 'driverProfile']);

        // Filtros
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
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')
                      ->paginate($request->get('per_page', 15));

        return response()->json($users);
    }

    public function show($id)
    {
        $user = User::with([
            'stores.company', 
            'driverProfile.vehicle',
            'companies',
            'orders'
        ])->findOrFail($id);

        return response()->json($user);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'type' => 'required|in:admin,seller,customer,driver',
            'phone' => 'required|string|max:20',
            'nif' => 'required|string|max:25|unique:users',
            'is_active' => 'boolean',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'type' => $request->type,
            'phone' => $request->phone,
            'nif' => $request->nif,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'message' => 'Utilizador criado com sucesso',
            'user' => $user
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('users')->ignore($user->id)
            ],
            'type' => 'sometimes|required|in:admin,seller,customer,driver',
            'phone' => 'sometimes|required|string|max:20',
            'nif' => [
                'sometimes',
                'required',
                'string',
                'max:25',
                Rule::unique('users')->ignore($user->id)
            ],
            'is_active' => 'boolean',
        ]);

        $user->update($request->all());

        return response()->json([
            'message' => 'Utilizador atualizado com sucesso',
            'user' => $user->fresh()
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // Verificar se o utilizador tem dados associados
        if ($user->orders()->exists() || $user->stores()->exists()) {
            return response()->json([
                'message' => 'Não é possível eliminar o utilizador porque tem dados associados'
            ], 422);
        }

        $user->delete();

        return response()->json([
            'message' => 'Utilizador eliminado com sucesso'
        ]);
    }

    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $user->update([
            'is_active' => !$user->is_active
        ]);

        return response()->json([
            'message' => 'Estado do utilizador atualizado',
            'user' => $user->fresh()
        ]);
    }
}