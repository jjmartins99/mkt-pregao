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
        $query = Store::with(['company', 'owner'])
            ->active()
            ->verified();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $stores = $query->orderBy('name')
            ->paginate($request->get('per_page', 15));

        return response()->json($stores);
    }

    public function show($id)
    {
        $store = Store::with(['company', 'owner', 'products' => function($query) {
            $query->active()->with(['category', 'images']);
        }])->active()->findOrFail($id);

        return response()->json($store);
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'phone' => 'required|string|max:20',
            'email' => 'required|email',
            'address' => 'required|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'banner' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        // Verifica se o usuário pertence à empresa
        $company = Company::where('id', $request->company_id)
            ->whereHas('users', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id)
                    ->where('role', 'owner');
            })->firstOrFail();

        $storeData = $request->except(['logo', 'banner']);
        $storeData['owner_id'] = $request->user()->id;
        $storeData['slug'] = \Str::slug($request->name);

        // Upload logo
        if ($request->hasFile('logo')) {
            $storeData['logo'] = $request->file('logo')->store('stores/logo', 'public');
        }

        // Upload banner
        if ($request->hasFile('banner')) {
            $storeData['banner'] = $request->file('banner')->store('stores/banner', 'public');
        }

        $store = Store::create($storeData);
        $store->load(['company', 'owner']);

        return response()->json($store, 201);
    }

    public function update(Request $request, $id)
    {
        $store = Store::where('id', $id)
            ->where('owner_id', $request->user()->id)
            ->firstOrFail();

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'phone' => 'sometimes|required|string|max:20',
            'email' => 'sometimes|required|email',
            'address' => 'sometimes|required|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'banner' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'is_active' => 'boolean',
        ]);

        $updateData = $request->except(['logo', 'banner']);

        // Upload logo
        if ($request->hasFile('logo')) {
            // Remove logo antiga
            if ($store->logo) {
                Storage::disk('public')->delete($store->logo);
            }
            $updateData['logo'] = $request->file('logo')->store('stores/logo', 'public');
        }

        // Upload banner
        if ($request->hasFile('banner')) {
            // Remove banner antigo
            if ($store->banner) {
                Storage::disk('public')->delete($store->banner);
            }
            $updateData['banner'] = $request->file('banner')->store('stores/banner', 'public');
        }

        $store->update($updateData);
        $store->load(['company', 'owner']);

        return response()->json($store);
    }
}