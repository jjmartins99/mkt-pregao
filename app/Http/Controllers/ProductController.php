<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['store', 'category', 'brand', 'unit', 'images'])
            ->active()
            ->whereHas('store', function ($q) {
                $q->where('is_active', true);
            });

        // Filtros
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('kind')) {
            $query->where('kind', $request->kind);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }

        // Ordenação
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $products = $query->paginate($request->get('per_page', 15));

        return response()->json($products);
    }

    public function show($id)
    {
        $product = Product::with([
            'store', 
            'category', 
            'brand', 
            'unit', 
            'images',
            'packaging',
            'prices' => function($query) {
                $query->orderBy('price', 'asc');
            }
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

        // Verifica se o usuário é dono da loja
        $store = Store::where('id', $request->store_id)
            ->where('owner_id', $request->user()->id)
            ->firstOrFail();

        $product = Product::create($request->except('images'));

        // Upload de imagens
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                
                $product->images()->create([
                    'image_path' => $path,
                    'is_primary' => false,
                ]);
            }
        }

        $product->load(['store', 'category', 'brand', 'unit', 'images']);

        return response()->json($product, 201);
    }

    public function update(Request $request, $id)
    {
        $product = Product::with('store')->findOrFail($id);

        // Verifica se o usuário é dono da loja
        if ($product->store->owner_id !== $request->user()->id) {
            return response()->json(['message' => 'Não autorizado'], 403);
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
        ]);

        $product->update($request->all());

        return response()->json($product);
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
}