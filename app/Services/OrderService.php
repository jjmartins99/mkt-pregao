<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\StockService;
use App\Exceptions\InsufficientStockException;
use Illuminate\Support\Facades\DB;

class OrderService
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function createOrderFromCart($user, $cart, $orderData)
    {
        return DB::transaction(function () use ($user, $cart, $orderData) {
            // Criar o pedido
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'customer_id' => $user->id,
                'store_id' => $cart->store_id,
                'status' => Order::STATUS_PENDING,
                'payment_status' => Order::PAYMENT_STATUS_PENDING,
                'payment_method' => $orderData['payment_method'],
                'delivery_address' => $orderData['delivery_address'],
                'notes' => $orderData['notes'] ?? null,
                'subtotal' => 0,
                'tax_amount' => 0,
                'shipping_cost' => 0,
                'total_amount' => 0,
            ]);

            $subtotal = 0;
            $warehouse = $cart->store->warehouses()->first();

            // Processar itens do carrinho
            foreach ($cart->items as $cartItem) {
                $product = $cartItem->product;
                $unitPrice = $product->getCurrentPrice();
                $itemTotal = $cartItem->quantity * $unitPrice;

                // Adicionar item ao pedido
                $orderItem = $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_description' => $product->description,
                    'sku' => $product->sku,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $itemTotal,
                ]);

                $subtotal += $itemTotal;

                // Processar stock se for mercadoria
                if ($product->isGood() && $product->track_stock && $warehouse) {
                    try {
                        $this->stockService->removeStock(
                            $product,
                            $warehouse,
                            $cartItem->quantity,
                            [
                                'reference_type' => 'order',
                                'reference_id' => $order->id,
                                'notes' => 'Venda - Pedido ' . $order->order_number,
                                'user_id' => $user->id,
                            ]
                        );
                    } catch (InsufficientStockException $e) {
                        // Reverter pedido se stock insuficiente
                        DB::rollBack();
                        throw $e;
                    }
                }

                // Atualizar contador de vendas
                $product->incrementSold($cartItem->quantity);
            }

            // Calcular totais
            $taxRate = 0.14; // IVA 14%
            $taxAmount = $subtotal * $taxRate;
            $shippingCost = $this->calculateShippingCost($order, $subtotal);
            $totalAmount = $subtotal + $taxAmount + $shippingCost;

            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'shipping_cost' => $shippingCost,
                'total_amount' => $totalAmount,
            ]);

            // Limpar carrinho
            $cart->items()->delete();
            $cart->delete();

            // Disparar evento de pedido criado
            event(new \App\Events\OrderCreated($order));

            return $order;
        });
    }

    public function cancelOrder(Order $order, $reason = null)
    {
        return DB::transaction(function () use ($order, $reason) {
            $oldStatus = $order->status;
            
            $order->update([
                'status' => Order::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            // Reverter stock se o pedido estava confirmado/processando
            if (in_array($oldStatus, [Order::STATUS_CONFIRMED, Order::STATUS_PROCESSING])) {
                $this->restoreOrderStock($order);
            }

            // Adicionar histórico
            $order->addStatusHistory(Order::STATUS_CANCELLED, $reason);

            // Disparar evento
            event(new \App\Events\OrderCancelled($order));

            return $order;
        });
    }

    private function generateOrderNumber()
    {
        return 'ORD' . date('Ymd') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    private function calculateShippingCost(Order $order, $subtotal)
    {
        // Lógica de cálculo de entrega
        if ($subtotal > 10000) { // 10,000 Kz
            return 0; // Entrega grátis
        }

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

    private function restoreOrderStock(Order $order)
    {
        $warehouse = $order->store->warehouses()->first();

        foreach ($order->items as $item) {
            if ($item->product->isGood() && $item->product->track_stock && $warehouse) {
                $this->stockService->addStock(
                    $item->product,
                    $warehouse,
                    $item->quantity,
                    [
                        'reference_type' => 'order_cancellation',
                        'reference_id' => $order->id,
                        'notes' => 'Cancelamento - Pedido ' . $order->order_number,
                        'user_id' => auth()->id(),
                    ]
                );
            }
        }
    }

    public function calculateOrderTotals(Order $order)
    {
        $subtotal = $order->items->sum('total_price');
        $taxAmount = $subtotal * 0.14; // IVA 14%
        $totalAmount = $subtotal + $taxAmount + $order->shipping_cost;

        $order->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ]);

        return $order;
    }
}