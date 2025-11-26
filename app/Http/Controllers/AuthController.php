<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\HasApiTokens;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'type' => 'required|in:customer,seller,driver',
            'phone' => 'required|string|max:20',
            'nif' => 'required|string|max:25|unique:users',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'type' => $request->type,
            'phone' => $request->phone,
            'nif' => $request->nif,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['A sua conta está desativada.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout realizado com sucesso']);
    }

    /*
    public function user(Request $request)
    {
        $user = $request->user();
        
        // Carrega relações adicionais baseadas no tipo de usuário
        if ($user->isSeller()) {
            $user->load('stores');
        } elseif ($user->isDriver()) {
            $user->load('driverProfile');
        }

        return response()->json($user);
    }
*/

  
// ...existing code...
    public function user(Request $request)
    {
        $user = $request->user();

        // Carrega relações adicionais baseadas no tipo de usuário
        $relationsByType = [
            'customer' => ['customerProfile', 'addresses', 'orders'],
            'seller' => ['stores', 'products'],
            'driver' => ['driverProfile', 'deliveries'],
        ];

        $type = $user->type ?? null;

        if ($type && isset($relationsByType[$type])) {
            $toLoad = array_filter($relationsByType[$type], fn($rel) => method_exists($user, $rel));
            if (!empty($toLoad)) {
                $user->load($toLoad);
            }
        }

        return response()->json($user);
    }
// ...existing code...
}