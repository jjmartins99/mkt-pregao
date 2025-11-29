<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Events\OrderCancelled;
use App\Services\StockService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateStock
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function handle(OrderCreated $event)
    {
        // O stock já é atualizado durante a criação do pedido
        // Este listener pode ser usado para operações adicionais
    }

    public function handleOrderCancelled(OrderCancelled $event)
    {
        $order = $event->order;
        $warehouse = $order->store->warehouses()->first();

        if (!$warehouse) {
            return;
        }

        foreach ($order->items as $item) {
            if ($item->product->isGood() && $item->product->track_stock) {
                $this->stockService->addStock(
                    $item->product,
                    $warehouse,
                    $item->quantity,
                    [
                        'reference_type' => 'order_cancellation',
                        'reference_id' => $order->id,
                        'notes' => 'Cancelamento do pedido ' . $order->order_number,
                    ]
                );
            }
        }
    }
}