<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with([
            'store.company',
            'category',
            'brand',
            'unit',
            'images',
            'prices'
        ])->active();

        // Filtros
        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->has('kind')) {
            $query->where('kind', $request->kind);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Ordenação
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSort = ['name', 'price', 'rating', 'total_sold', 'created_at'];
        if (in_array($sortBy, $allowedSort)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $products = $query->paginate($request->get('per_page', 15));

        return response()->json($products);
    }

    public function show($id)
    {
        $product = Product::with([
            'store.company',
            'category',
            'brand',
            'unit',
            'images',
            'prices',
            'packaging',
            'reviews.user'
        ])->active()->findOrFail($id);

        return response()->json($product);
    }

    public function store(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sku' => 'required|string|unique:products,sku',
            'barcode' => 'nullable|string',
            'kind' => 'required|in:good,service',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'unit_id' => 'required|exists:units,id',
            'weight' => 'nullable|numeric|min:0',
            'track_stock' => 'boolean',
            'requires_expiry' => 'boolean',
            'requires_batch' => 'boolean',
            'picking_policy' => 'in:fifo,lifo,fefo',
            'min_stock' => 'nullable|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = $request->user();
        $store = Store::findOrFail($request->store_id);

        // Verificar se o utilizador é dono da loja
        if ($store->owner_id !== $user->id && !$user->isAdmin()) {
            return response()->json([
                'message' => 'Não tem permissão para adicionar produtos a esta loja'
            ], 403);
        }

        $product = Product::create($request->except('images'));

        // Upload de imagens
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('products', 'public');
                
                $product->images()->create([
                    'image_path' => $path,
                    'is_primary' => $index === 0,
                    'order' => $index,
                ]);
            }
        }

        // Criar preço padrão
        $product->prices()->create([
            'price' => $request->price ?? 0,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Produto criado com sucesso',
            'product' => $product->load(['store', 'category', 'brand', 'unit', 'images'])
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $product = Product::with('store')->findOrFail($id);
        $user = $request->user();

        // Verificar se o utilizador é dono da loja
        if ($product->store->owner_id !== $user->id && !$user->isAdmin()) {
            return response()->json([
                'message' => 'Não tem permissão para atualizar este produto'
            ], 403);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'barcode' => 'nullable|string',
            'category_id' => 'sometimes|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'unit_id' => 'sometimes|exists:units,id',
            'weight' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'min_stock' => 'nullable|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $product->update($request->except('images'));

        // Adicionar novas imagens
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                
                $product->images()->create([
                    'image_path' => $path,
                    'is_primary' => false,
                ]);
            }
        }

        return response()->json([
            'message' => 'Produto atualizado com sucesso',
            'product' => $product->fresh(['store', 'category', 'brand', 'unit', 'images'])
        ]);
    }

    public function myProducts(Request $request)
    {
        $user = $request->user();
        
        $products = Product::with(['store', 'category', 'brand', 'unit', 'images'])
            ->whereHas('store', function ($query) use ($user) {
                $query->where('owner_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($products);
    }

    public function toggleStatus($id)
    {
        $product = Product::findOrFail($id);
        $product->update([
            'is_active' => !$product->is_active
        ]);

        return response()->json([
            'message' => 'Estado do produto atualizado',
            'product' => $product->fresh()
        ]);
    }

    public function updatePrimaryImage(Request $request, $productId, $imageId)
    {
        $product = Product::findOrFail($productId);
        $user = $request->user();

        // Verificar se o utilizador é dono da loja
        if ($product->store->owner_id !== $user->id && !$user->isAdmin()) {
            return response()->json([
                'message' => 'Não tem permissão para atualizar este produto'
            ], 403);
        }

        // Remover primary de todas as imagens
        $product->images()->update(['is_primary' => false]);

        // Definir nova imagem como primary
        $image = $product->images()->findOrFail($imageId);
        $image->update(['is_primary' => true]);

        return response()->json([
            'message' => 'Imagem principal atualizada com sucesso'
        ]);
    }

    public function deleteImage($productId, $imageId)
    {
        $product = Product::findOrFail($productId);
        $image = $product->images()->findOrFail($imageId);

        // Não permitir eliminar a única imagem
        if ($product->images()->count() <= 1) {
            return response()->json([
                'message' => 'Não é possível eliminar a única imagem do produto'
            ], 422);
        }

        // Eliminar ficheiro
        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        // Se era a imagem primary, definir outra como primary
        if ($image->is_primary) {
            $newPrimary = $product->images()->first();
            if ($newPrimary) {
                $newPrimary->update(['is_primary' => true]);
            }
        }

        return response()->json([
            'message' => 'Imagem eliminada com sucesso'
        ]);
    }
}