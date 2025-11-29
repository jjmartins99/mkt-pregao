<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\Company;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $query = Warehouse::with(['company', 'branch'])->active();

        // Filtros
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $warehouses = $query->orderBy('name')
                           ->paginate($request->get('per_page', 15));

        return response()->json($warehouses);
    }

    public function show($id)
    {
        $warehouse = Warehouse::with([
            'company',
            'branch',
            'stocks.product.store',
            'stocks.product.images'
        ])->findOrFail($id);

        // Adicionar estatísticas
        $warehouse->loadCount(['stocks as total_products' => function($query) {
            $query->where('quantity', '>', 0);
        }]);

        $warehouse->total_value = $warehouse->stocks()
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->join('product_prices', function ($join) {
                $join->on('products.id', '=', 'product_prices.product_id')
                     ->where('product_prices.is_active', true);
            })
            ->sum(\DB::raw('stocks.quantity * product_prices.cost_price'));

        return response()->json($warehouse);
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:warehouses,code',
            'address' => 'required|string',
            'city' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'area' => 'nullable|numeric|min:0',
            'type' => 'required|in:main,secondary,transit,quarantine',
        ]);

        $company = Company::findOrFail($request->company_id);

        // Verificar permissões
        $user = $request->user();
        if (!$company->users()->where('user_id', $user->id)->exists() && !$user->isAdmin()) {
            return response()->json([
                'message' => 'Não tem permissão para criar armazéns nesta empresa'
            ], 403);
        }

        $warehouse = Warehouse::create($request->all());

        return response()->json([
            'message' => 'Armazém criado com sucesso',
            'warehouse' => $warehouse->load(['company', 'branch'])
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $warehouse = Warehouse::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'address' => 'sometimes|required|string',
            'city' => 'sometimes|required|string|max:100',
            'postal_code' => 'sometimes|required|string|max:20',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'area' => 'nullable|numeric|min:0',
            'type' => 'sometimes|required|in:main,secondary,transit,quarantine',
            'is_active' => 'boolean',
        ]);

        $warehouse->update($request->all());

        return response()->json([
            'message' => 'Armazém atualizado com sucesso',
            'warehouse' => $warehouse->fresh(['company', 'branch'])
        ]);
    }

    public function getWarehouseStats($id)
    {
        $warehouse = Warehouse::findOrFail($id);

        $stats = [
            'total_products' => $warehouse->stocks()->where('quantity', '>', 0)->count(),
            'total_quantity' => $warehouse->stocks()->sum('quantity'),
            'total_value' => $warehouse->stocks()
                ->join('products', 'stocks.product_id', '=', 'products.id')
                ->join('product_prices', function ($join) {
                    $join->on('products.id', '=', 'product_prices.product_id')
                         ->where('product_prices.is_active', true);
                })
                ->sum(\DB::raw('stocks.quantity * product_prices.cost_price')),
            'low_stock_items' => $warehouse->stocks()
                                         ->whereRaw('available_quantity <= min_stock')
                                         ->where('min_stock', '>', 0)
                                         ->count(),
            'out_of_stock_items' => $warehouse->stocks()
                                            ->where('available_quantity', '<=', 0)
                                            ->count(),
        ];

        return response()->json($stats);
    }

    public function getWarehouseProducts($id)
    {
        $warehouse = Warehouse::findOrFail($id);
        
        $products = $warehouse->stocks()
                            ->with(['product.store', 'product.images'])
                            ->where('quantity', '>', 0)
                            ->paginate(20);

        return response()->json($products);
    }
}