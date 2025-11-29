<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index(Request $request)
    {
        $query = Stock::with(['product.store', 'warehouse'])
                     ->where('quantity', '>', 0);

        // Filtros
        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('store_id')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('store_id', $request->store_id);
            });
        }

        if ($request->has('low_stock')) {
            $query->whereRaw('available_quantity <= min_stock');
        }

        if ($request->has('out_of_stock')) {
            $query->where('available_quantity', '<=', 0);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $stocks = $query->orderBy('available_quantity')
                       ->paginate($request->get('per_page', 15));

        return response()->json($stocks);
    }

    public function show($id)
    {
        $stock = Stock::with([
            'product.store',
            'warehouse',
            'product.stockBatches' => function($query) use ($id) {
                $query->where('warehouse_id', $id);
            }
        ])->findOrFail($id);

        return response()->json($stock);
    }

    public function addStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|numeric|min:0.001',
            'cost_price' => 'nullable|numeric|min:0',
            'batch_number' => 'nullable|string|max:100',
            'expiry_date' => 'nullable|date|after:today',
            'notes' => 'nullable|string',
        ]);

        $product = Product::findOrFail($request->product_id);
        $warehouse = Warehouse::findOrFail($request->warehouse_id);

        // Verificar permissões
        $user = $request->user();
        if (!$this->canManageStock($user, $product)) {
            return response()->json([
                'message' => 'Não tem permissão para gerir stock deste produto'
            ], 403);
        }

        return DB::transaction(function () use ($product, $warehouse, $request) {
            // Adicionar stock
            $stock = $this->stockService->addStock(
                $product,
                $warehouse,
                $request->quantity,
                [
                    'reference_type' => 'manual',
                    'reference_id' => null,
                    'notes' => $request->notes ?? 'Entrada manual de stock'
                ]
            );

            // Criar lote se necessário
            if ($request->batch_number || $request->expiry_date) {
                $batch = $product->stockBatches()->create([
                    'warehouse_id' => $warehouse->id,
                    'batch_number' => $request->batch_number ?? 'LOTE-' . time(),
                    'expiry_date' => $request->expiry_date,
                    'quantity' => $request->quantity,
                    'cost_price' => $request->cost_price,
                ]);
            }

            return response()->json([
                'message' => 'Stock adicionado com sucesso',
                'stock' => $stock->fresh(['product', 'warehouse'])
            ], 201);
        });
    }

    public function adjustStock(Request $request, $id)
    {
        $request->validate([
            'adjustment_type' => 'required|in:add,remove,set',
            'quantity' => 'required|numeric|min:0.001',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $stock = Stock::with(['product', 'warehouse'])->findOrFail($id);

        // Verificar permissões
        $user = $request->user();
        if (!$this->canManageStock($user, $stock->product)) {
            return response()->json([
                'message' => 'Não tem permissão para ajustar stock deste produto'
            ], 403);
        }

        return DB::transaction(function () use ($stock, $request) {
            $oldQuantity = $stock->quantity;
            $newQuantity = $oldQuantity;

            switch ($request->adjustment_type) {
                case 'add':
                    $newQuantity = $oldQuantity + $request->quantity;
                    $movementType = 'IN';
                    break;
                case 'remove':
                    if ($request->quantity > $stock->available_quantity) {
                        return response()->json([
                            'message' => 'Quantidade a remover excede o stock disponível'
                        ], 422);
                    }
                    $newQuantity = $oldQuantity - $request->quantity;
                    $movementType = 'OUT';
                    break;
                case 'set':
                    $newQuantity = $request->quantity;
                    $movementType = $newQuantity > $oldQuantity ? 'IN' : 'OUT';
                    break;
            }

            // Atualizar stock
            $stock->update([
                'quantity' => $newQuantity,
                'available_quantity' => $newQuantity - $stock->reserved_quantity
            ]);

            // Registrar movimento
            StockMovement::create([
                'product_id' => $stock->product_id,
                'warehouse_id' => $stock->warehouse_id,
                'type' => 'ADJUSTMENT',
                'quantity' => abs($newQuantity - $oldQuantity),
                'balance' => $newQuantity,
                'reference_type' => 'adjustment',
                'notes' => $request->reason . ($request->notes ? ': ' . $request->notes : ''),
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Stock ajustado com sucesso',
                'stock' => $stock->fresh(),
                'adjustment' => [
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => $newQuantity,
                    'difference' => $newQuantity - $oldQuantity
                ]
            ]);
        });
    }

    public function transferStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'quantity' => 'required|numeric|min:0.001',
            'notes' => 'nullable|string',
        ]);

        $product = Product::findOrFail($request->product_id);
        $fromWarehouse = Warehouse::findOrFail($request->from_warehouse_id);
        $toWarehouse = Warehouse::findOrFail($request->to_warehouse_id);

        // Verificar permissões
        $user = $request->user();
        if (!$this->canManageStock($user, $product)) {
            return response()->json([
                'message' => 'Não tem permissão para transferir stock deste produto'
            ], 403);
        }

        // Verificar stock disponível
        $fromStock = Stock::where('product_id', $product->id)
                         ->where('warehouse_id', $fromWarehouse->id)
                         ->firstOrFail();

        if ($fromStock->available_quantity < $request->quantity) {
            return response()->json([
                'message' => 'Stock insuficiente no armazém de origem'
            ], 422);
        }

        return DB::transaction(function () use ($product, $fromWarehouse, $toWarehouse, $request) {
            // Transferir stock
            $this->stockService->transferStock(
                $product,
                $fromWarehouse,
                $toWarehouse,
                $request->quantity,
                [
                    'transfer_id' => null,
                    'notes' => $request->notes ?? 'Transferência entre armazéns'
                ]
            );

            return response()->json([
                'message' => 'Stock transferido com sucesso'
            ]);
        });
    }

    public function getStockMovements(Request $request)
    {
        $query = StockMovement::with(['product', 'warehouse', 'user', 'batch'])
                             ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $movements = $query->paginate($request->get('per_page', 15));

        return response()->json($movements);
    }

    public function getLowStockAlerts(Request $request)
    {
        $query = Stock::with(['product.store', 'warehouse'])
                     ->whereRaw('available_quantity <= min_stock')
                     ->where('min_stock', '>', 0);

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->has('store_id')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('store_id', $request->store_id);
            });
        }

        $alerts = $query->orderBy('available_quantity')
                       ->paginate($request->get('per_page', 15));

        return response()->json($alerts);
    }

    public function getStockHistory($productId, $warehouseId)
    {
        $stock = Stock::with(['product', 'warehouse'])
                     ->where('product_id', $productId)
                     ->where('warehouse_id', $warehouseId)
                     ->firstOrFail();

        $movements = StockMovement::with(['user'])
                                 ->where('product_id', $productId)
                                 ->where('warehouse_id', $warehouseId)
                                 ->orderBy('created_at', 'desc')
                                 ->paginate(20);

        $batches = $stock->product->stockBatches()
                         ->where('warehouse_id', $warehouseId)
                         ->where('quantity', '>', 0)
                         ->orderBy('expiry_date')
                         ->get();

        return response()->json([
            'stock' => $stock,
            'movements' => $movements,
            'batches' => $batches
        ]);
    }

    private function canManageStock($user, $product)
    {
        if ($user->isAdmin()) return true;
        if ($user->isSeller() && $product->store->owner_id === $user->id) return true;
        
        return false;
    }

    public function getStockStats(Request $request)
    {
        $user = $request->user();
        $query = Stock::query();

        if ($user->isSeller()) {
            $query->whereHas('product', function ($q) use ($user) {
                $q->whereHas('store', function ($q) use ($user) {
                    $q->where('owner_id', $user->id);
                });
            });
        }

        $stats = [
            'total_products' => $query->distinct('product_id')->count('product_id'),
            'total_quantity' => $query->sum('quantity'),
            'total_value' => $query->join('products', 'stocks.product_id', '=', 'products.id')
                                 ->join('product_prices', function ($join) {
                                     $join->on('products.id', '=', 'product_prices.product_id')
                                          ->where('product_prices.is_active', true);
                                 })
                                 ->sum(DB::raw('stocks.quantity * product_prices.cost_price')),
            'low_stock_items' => $query->clone()
                                      ->whereRaw('available_quantity <= min_stock')
                                      ->where('min_stock', '>', 0)
                                      ->count(),
            'out_of_stock_items' => $query->clone()
                                         ->where('available_quantity', '<=', 0)
                                         ->count(),
        ];

        return response()->json($stats);
    }
}