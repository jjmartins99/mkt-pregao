<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $query = Brand::withCount('products')->active();

        // Filtros
        if ($request->has('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $brands = $query->orderBy('name')
                       ->paginate($request->get('per_page', 15));

        return response()->json($brands);
    }

    public function show($id)
    {
        $brand = Brand::with(['products' => function($query) {
            $query->active()->with(['store', 'images']);
        }])->findOrFail($id);

        return response()->json($brand);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:brands',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_featured' => 'boolean',
        ]);

        $data = $request->all();
        $data['slug'] = \Str::slug($request->name);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('brands', 'public');
        }

        $brand = Brand::create($data);

        return response()->json([
            'message' => 'Marca criada com sucesso',
            'brand' => $brand
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $brand = Brand::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:brands,name,' . $brand->id,
            'description' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $data = $request->all();

        if ($request->hasFile('logo')) {
            // Remove logo antiga
            if ($brand->logo) {
                Storage::disk('public')->delete($brand->logo);
            }
            $data['logo'] = $request->file('logo')->store('brands', 'public');
        }

        $brand->update($data);

        return response()->json([
            'message' => 'Marca atualizada com sucesso',
            'brand' => $brand->fresh()
        ]);
    }

    public function destroy($id)
    {
        $brand = Brand::findOrFail($id);

        // Verificar se a marca tem produtos
        if ($brand->products()->exists()) {
            return response()->json([
                'message' => 'Não é possível eliminar a marca porque tem produtos associados'
            ], 422);
        }

        // Remove logo
        if ($brand->logo) {
            Storage::disk('public')->delete($brand->logo);
        }

        $brand->delete();

        return response()->json([
            'message' => 'Marca eliminada com sucesso'
        ]);
    }

    public function toggleStatus($id)
    {
        $brand = Brand::findOrFail($id);
        $brand->update([
            'is_active' => !$brand->is_active
        ]);

        return response()->json([
            'message' => 'Estado da marca atualizado',
            'brand' => $brand->fresh()
        ]);
    }

    public function toggleFeatured($id)
    {
        $brand = Brand::findOrFail($id);
        $brand->update([
            'is_featured' => !$brand->is_featured
        ]);

        return response()->json([
            'message' => 'Estado de destaque da marca atualizado',
            'brand' => $brand->fresh()
        ]);
    }
}