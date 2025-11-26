<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Order::with(['store', 'items', 'driver']);

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

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'payment_method' => 'required|in:cash,card,transfer,mobile',
            'delivery_address' => 'required|array',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();
        $cart = Cart::with('items.product')->where('user_id', $user->id)->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Carrinho vazio'], 400);
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
                $orderItem = $order->items()->create([
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->product->prices()->first()->price,
                    'total_price' => $cartItem->quantity * $cartItem->product->prices()->first()->price,
                ]);

                $subtotal += $orderItem->total_price;

                // Atualiza stock se for mercadoria
                if ($cartItem->product->isGood() && $cartItem->product->track_stock) {
                    // Implementar lógica de redução de stock
                    $this->reduceStock($cartItem->product, $cartItem->quantity);
                }
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

            // Limpa carrinho
            $cart->items()->delete();
            $cart->delete();

            $order->load(['store', 'items.product']);

            return response()->json($order, 201);
        });
    }

    public function show($id)
    {
        $order = Order::with(['store', 'items.product', 'driver.vehicle'])
            ->findOrFail($id);

        return response()->json($order);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled',
        ]);

        $order = Order::findOrFail($id);
        $user = $request->user();

        // Verifica permissões
        if (!$this->canUpdateOrderStatus($user, $order)) {
            return response()->json(['message' => 'Não autorizado'], 403);
        }

        $order->update(['status' => $request->status]);

        // Se cancelado, reverter stock
        if ($request->status === Order::STATUS_CANCELLED) {
            $this->restoreStock($order);
        }

        return response()->json($order);
    }

    private function reduceStock($product, $quantity)
    {
        // Implementar lógica FIFO/LIFO para redução de stock
        // Esta é uma implementação simplificada
        $product->stocks()
            ->where('quantity', '>', 0)
            ->orderBy('created_at', 'asc')
            ->decrement('quantity', $quantity);
    }

    private function restoreStock($order)
    {
        foreach ($order->items as $item) {
            if ($item->product->isGood() && $item->product->track_stock) {
                $item->product->stocks()
                    ->increment('quantity', $item->quantity);
            }
        }
    }

    private function calculateShipping($order, $subtotal)
    {
        // Implementar lógica de cálculo de entrega
        // Por enquanto, retorna valor fixo baseado no subtotal
        if ($subtotal > 10000) {
            return 0; // Entrega grátis para pedidos acima de 10.000 Kz
        }
        return 500; // 500 Kz fixos
    }

    private function canUpdateOrderStatus($user, $order)
    {
        if ($user->isAdmin()) return true;
        if ($user->isSeller() && $order->store->owner_id === $user->id) return true;
        if ($user->isDriver() && $order->delivery_driver_id === $user->driverProfile->id) return true;
        
        return false;
    }
}