<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Cart;
use App\Models\CartItem;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = Order::with(['store', 'items.product', 'driver.vehicle']);

        if ($user->isCustomer()) {
            $query->where('customer_id', $user->id);
        } elseif ($user->isSeller()) {
            $query->whereHas('store', function ($q) use ($user) {
                $q->where('owner_id', $user->id);
            });
        } elseif ($user->isDriver()) {
            $query->where('delivery_driver_id', $user->driverProfile->id);
        }

        // Filtros
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->orderBy('created_at', 'desc')
                       ->paginate($request->get('per_page', 15));

        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id' => 'required|exists:stores,id',
            'payment_method' => 'required|in:cash,card,transfer,mobile',
            'delivery_address' => 'required|array',
            'delivery_address.street' => 'required|string',
            'delivery_address.city' => 'required|string',
            'delivery_address.province' => 'required|string',
            'delivery_address.postal_code' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $cart = Cart::with(['items.product.prices', 'items.product.store'])
                   ->where('user_id', $user->id)
                   ->where('store_id', $request->store_id)
                   ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'message' => 'Carrinho vazio'
            ], 400);
        }

        return DB::transaction(function () use ($user, $cart, $request) {
            // Cria o pedido
            $order = Order::create([
                'order_number' => 'ORD' . time() . rand(1000, 9999),
                'customer_id' => $user->id,
                'store_id' => $request->store_id,
                'status' => Order::STATUS_PENDING,
                'payment_status' => Order::PAYMENT_STATUS_PENDING,
                'payment_method' => $request->payment_method,
                'delivery_address' => $request->delivery_address,
                'notes' => $request->notes,
                'subtotal' => 0,
                'tax_amount' => 0,
                'shipping_cost' => 0,
                'total_amount' => 0,
            ]);

            $subtotal = 0;

            // Adiciona itens ao pedido
            foreach ($cart->items as $cartItem) {
                $product = $cartItem->product;
                $unitPrice = $product->getCurrentPrice();

                $orderItem = $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_description' => $product->description,
                    'sku' => $product->sku,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $cartItem->quantity * $unitPrice,
                ]);

                $subtotal += $orderItem->total_price;

                // Atualizar stock se for mercadoria
                if ($product->isGood() && $product->track_stock) {
                    $warehouse = $product->store->warehouses()->first();
                    if ($warehouse) {
                        $this->stockService->removeStock(
                            $product,
                            $warehouse,
                            $cartItem->quantity,
                            [
                                'reference_type' => 'order',
                                'reference_id' => $order->id,
                                'notes' => 'Venda - Pedido ' . $order->order_number
                            ]
                        );
                    }
                }

                // Incrementar contador de vendas
                $product->incrementSold($cartItem->quantity);
            }

            // Calcula totais
            $taxRate = 0.14; // IVA 14%
            $taxAmount = $subtotal * $taxRate;
            $shippingCost = $this->calculateShipping($order, $subtotal);
            $totalAmount = $subtotal + $taxAmount + $shippingCost;

            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'shipping_cost' => $shippingCost,
                'total_amount' => $totalAmount,
            ]);

            // Adiciona histórico
            $order->addStatusHistory(Order::STATUS_PENDING, 'Pedido criado');

            // Limpa carrinho
            $cart->items()->delete();
            $cart->delete();

            $order->load(['store', 'items.product', 'customer']);

            return response()->json([
                'message' => 'Pedido criado com sucesso',
                'order' => $order
            ], 201);
        });
    }

    public function show($id)
    {
        $order = Order::with([
            'store.company',
            'items.product.images',
            'customer',
            'driver.vehicle',
            'statusHistory.user',
            'deliveryTracking'
        ])->findOrFail($id);

        return response()->json($order);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled',
            'notes' => 'nullable|string',
        ]);

        $order = Order::findOrFail($id);
        $user = $request->user();

        // Verifica permissões
        if (!$this->canUpdateOrderStatus($user, $order)) {
            return response()->json([
                'message' => 'Não autorizado'
            ], 403);
        }

        $oldStatus = $order->status;
        $order->update(['status' => $request->status]);

        // Adiciona histórico
        $order->addStatusHistory($request->status, $request->notes, $user->id);

        // Se cancelado, reverter stock
        if ($request->status === Order::STATUS_CANCELLED && $oldStatus !== Order::STATUS_CANCELLED) {
            $this->restoreStock($order);
        }

        // Se entregue, marcar como entregue
        if ($request->status === Order::STATUS_DELIVERED) {
            $order->markAsDelivered();
        }

        return response()->json([
            'message' => 'Status do pedido atualizado',
            'order' => $order->fresh(['store', 'items.product'])
        ]);
    }

    public function updatePaymentStatus(Request $request, $id)
    {
        $request->validate([
            'payment_status' => 'required|in:pending,paid,failed,refunded,partially_refunded',
        ]);

        $order = Order::findOrFail($id);
        $order->update(['payment_status' => $request->payment_status]);

        return response()->json([
            'message' => 'Status de pagamento atualizado',
            'order' => $order->fresh()
        ]);
    }

    public function assignDriver(Request $request, $id)
    {
        $request->validate([
            'driver_id' => 'required|exists:drivers,id',
            'estimated_delivery_time' => 'nullable|date',
        ]);

        $order = Order::findOrFail($id);
        
        $order->update([
            'delivery_driver_id' => $request->driver_id,
            'estimated_delivery_time' => $request->estimated_delivery_time,
        ]);

        // Criar tracking de entrega
        $order->deliveryTracking()->create([
            'driver_id' => $request->driver_id,
            'status' => 'assigned',
            'status_changed_at' => now(),
        ]);

        // Adicionar histórico
        $order->addStatusHistory('assigned', 'Motorista atribuído: ' . $order->driver->user->name);

        return response()->json([
            'message' => 'Motorista atribuído com sucesso',
            'order' => $order->fresh(['driver.vehicle', 'driver.user'])
        ]);
    }

    private function calculateShipping($order, $subtotal)
    {
        // Lógica simplificada de cálculo de entrega
        if ($subtotal > 10000) {
            return 0; // Entrega grátis para pedidos acima de 10.000 Kz
        }
        
        // Baseado na cidade/província
        $address = $order->delivery_address;
        $city = strtolower($address['city'] ?? '');
        
        $shippingRates = [
            'luanda' => 500,
            'benguela' => 800,
            'huambo' => 1000,
            'default' => 700,
        ];
        
        return $shippingRates[$city] ?? $shippingRates['default'];
    }

    private function canUpdateOrderStatus($user, $order)
    {
        if ($user->isAdmin()) return true;
        if ($user->isSeller() && $order->store->owner_id === $user->id) return true;
        if ($user->isDriver() && $order->delivery_driver_id === $user->driverProfile->id) return true;
        
        return false;
    }

    private function restoreStock($order)
    {
        foreach ($order->items as $item) {
            if ($item->product->isGood() && $item->product->track_stock) {
                $warehouse = $item->product->store->warehouses()->first();
                if ($warehouse) {
                    $this->stockService->addStock(
                        $item->product,
                        $warehouse,
                        $item->quantity,
                        [
                            'reference_type' => 'order',
                            'reference_id' => $order->id,
                            'notes' => 'Cancelamento - Pedido ' . $order->order_number
                        ]
                    );
                }
            }
        }
    }

    public function getOrderStats(Request $request)
    {
        $user = $request->user();
        $query = Order::query();

        if ($user->isSeller()) {
            $query->whereHas('store', function ($q) use ($user) {
                $q->where('owner_id', $user->id);
            });
        } elseif ($user->isCustomer()) {
            $query->where('customer_id', $user->id);
        } elseif ($user->isDriver()) {
            $query->where('delivery_driver_id', $user->driverProfile->id);
        }

        $stats = [
            'total_orders' => $query->count(),
            'pending_orders' => $query->where('status', 'pending')->count(),
            'processing_orders' => $query->where('status', 'processing')->count(),
            'shipped_orders' => $query->where('status', 'shipped')->count(),
            'delivered_orders' => $query->where('status', 'delivered')->count(),
            'cancelled_orders' => $query->where('status', 'cancelled')->count(),
            'total_revenue' => $query->where('status', 'delivered')->sum('total_amount'),
        ];

        return response()->json($stats);
    }
}