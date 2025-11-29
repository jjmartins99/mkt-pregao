<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        $query = Store::with(['company', 'owner', 'products'])
                     ->active()
                     ->verified();

        // Filtros
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('is_verified')) {
            $query->where('is_verified', $request->boolean('is_verified'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $stores = $query->orderBy('created_at', 'desc')
                       ->paginate($request->get('per_page', 15));

        return response()->json($stores);
    }

    public function show($id)
    {
        $store = Store::with([
            'company',
            'owner',
            'products.category',
            'products.images',
            'products.prices',
            'orders'
        ])->findOrFail($id);

        return response()->json($store);
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255|unique:stores',
            'description' => 'nullable|string',
            'phone' => 'required|string|max:20',
            'email' => 'required|email',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'banner' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'business_hours' => 'nullable|array',
        ]);

        // Verificar se o utilizador pertence à empresa
        $user = $request->user();
        $company = Company::findOrFail($request->company_id);
        
        if (!$company->users()->where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'Não tem permissão para criar lojas nesta empresa'
            ], 403);
        }

        $data = $request->all();
        $data['owner_id'] = $user->id;
        $data['slug'] = \Str::slug($request->name);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('stores/logo', 'public');
        }

        if ($request->hasFile('banner')) {
            $data['banner'] = $request->file('banner')->store('stores/banner', 'public');
        }

        $store = Store::create($data);

        return response()->json([
            'message' => 'Loja criada com sucesso',
            'store' => $store->load(['company', 'owner'])
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $store = Store::findOrFail($id);
        $user = $request->user();

        // Verificar se o utilizador é dono da loja
        if ($store->owner_id !== $user->id && !$user->isAdmin()) {
            return response()->json([
                'message' => 'Não tem permissão para atualizar esta loja'
            ], 403);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:stores,name,' . $store->id,
            'description' => 'nullable|string',
            'phone' => 'sometimes|required|string|max:20',
            'email' => 'sometimes|required|email',
            'address' => 'sometimes|required|string',
            'city' => 'sometimes|required|string|max:100',
            'postal_code' => 'sometimes|required|string|max:20',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'banner' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'business_hours' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $data = $request->all();

        if ($request->hasFile('logo')) {
            // Remove logo antiga
            if ($store->logo) {
                Storage::disk('public')->delete($store->logo);
            }
            $data['logo'] = $request->file('logo')->store('stores/logo', 'public');
        }

        if ($request->hasFile('banner')) {
            // Remove banner antigo
            if ($store->banner) {
                Storage::disk('public')->delete($store->banner);
            }
            $data['banner'] = $request->file('banner')->store('stores/banner', 'public');
        }

        $store->update($data);

        return response()->json([
            'message' => 'Loja atualizada com sucesso',
            'store' => $store->fresh(['company', 'owner'])
        ]);
    }

    public function myStores(Request $request)
    {
        $user = $request->user();
        $stores = Store::with(['company', 'products'])
                      ->where('owner_id', $user->id)
                      ->orderBy('created_at', 'desc')
                      ->paginate($request->get('per_page', 15));

        return response()->json($stores);
    }

    public function toggleVerification($id)
    {
        $store = Store::findOrFail($id);
        $store->update([
            'is_verified' => !$store->is_verified
        ]);

        return response()->json([
            'message' => 'Estado de verificação da loja atualizado',
            'store' => $store->fresh()
        ]);
    }

    public function getStoreStats($id)
    {
        $store = Store::findOrFail($id);
        
        $stats = [
            'total_products' => $store->products()->count(),
            'active_products' => $store->products()->active()->count(),
            'total_orders' => $store->orders()->count(),
            'completed_orders' => $store->orders()->where('status', 'delivered')->count(),
            'total_revenue' => $store->orders()->where('status', 'delivered')->sum('total_amount'),
            'average_rating' => $store->rating,
            'total_reviews' => $store->total_reviews,
        ];

        return response()->json($stats);
    }
}