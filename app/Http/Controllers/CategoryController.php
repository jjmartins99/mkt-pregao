<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::with(['parent', 'children'])
                        ->active();

        // Filtros
        if ($request->has('parent_id')) {
            if ($request->parent_id === 'null') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        }

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

        $categories = $query->orderBy('order')
                           ->orderBy('name')
                           ->paginate($request->get('per_page', 15));

        return response()->json($categories);
    }

    public function tree()
    {
        $categories = Category::with(['children.children'])
                             ->whereNull('parent_id')
                             ->active()
                             ->orderBy('order')
                             ->orderBy('name')
                             ->get();

        return response()->json($categories);
    }

    public function show($id)
    {
        $category = Category::with([
            'parent',
            'children',
            'products' => function($query) {
                $query->active()->with(['store', 'images']);
            }
        ])->findOrFail($id);

        return response()->json($category);
    }

    public function store(Request $request)
    {
        $request->validate([
            'parent_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255|unique:categories',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'order' => 'integer|min:0',
            'is_featured' => 'boolean',
        ]);

        $data = $request->all();
        $data['slug'] = \Str::slug($request->name);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $category = Category::create($data);

        return response()->json([
            'message' => 'Categoria criada com sucesso',
            'category' => $category->load(['parent', 'children'])
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'parent_id' => 'nullable|exists:categories,id',
            'name' => 'sometimes|required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'order' => 'integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $data = $request->all();

        if ($request->hasFile('image')) {
            // Remove imagem antiga
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $category->update($data);

        return response()->json([
            'message' => 'Categoria atualizada com sucesso',
            'category' => $category->fresh(['parent', 'children'])
        ]);
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // Verificar se a categoria tem produtos
        if ($category->products()->exists()) {
            return response()->json([
                'message' => 'Não é possível eliminar a categoria porque tem produtos associados'
            ], 422);
        }

        // Verificar se a categoria tem subcategorias
        if ($category->children()->exists()) {
            return response()->json([
                'message' => 'Não é possível eliminar a categoria porque tem subcategorias'
            ], 422);
        }

        // Remove imagem
        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        $category->delete();

        return response()->json([
            'message' => 'Categoria eliminada com sucesso'
        ]);
    }

    public function toggleStatus($id)
    {
        $category = Category::findOrFail($id);
        $category->update([
            'is_active' => !$category->is_active
        ]);

        return response()->json([
            'message' => 'Estado da categoria atualizado',
            'category' => $category->fresh()
        ]);
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:categories,id',
            'categories.*.order' => 'required|integer|min:0',
        ]);

        foreach ($request->categories as $item) {
            Category::where('id', $item['id'])->update(['order' => $item['order']]);
        }

        return response()->json([
            'message' => 'Ordem das categorias atualizada com sucesso'
        ]);
    }
}