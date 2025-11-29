<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendOrderNotification
{
    public function handle(OrderCreated $event)
    {
        $order = $event->order;

        // Notificar cliente
        Notification::createNotification(
            $order->customer_id,
            'order_created',
            'Novo Pedido Criado',
            "O seu pedido #{$order->order_number} foi criado com sucesso.",
            ['order_id' => $order->id]
        );

        // Notificar dono da loja
        Notification::createNotification(
            $order->store->owner_id,
            'order_created',
            'Novo Pedido Recebido',
            "Recebeu um novo pedido #{$order->order_number}.",
            ['order_id' => $order->id]
        );
    }

    public function handleStatusUpdate(OrderStatusUpdated $event)
    {
        $order = $event->order;
        $statusMessages = [
            'confirmed' => 'foi confirmado',
            'processing' => 'está a ser processado',
            'shipped' => 'foi enviado',
            'delivered' => 'foi entregue',
            'cancelled' => 'foi cancelado',
        ];

        if (isset($statusMessages[$event->newStatus])) {
            Notification::createNotification(
                $order->customer_id,
                'order_status_changed',
                'Status do Pedido Atualizado',
                "O seu pedido #{$order->order_number} {$statusMessages[$event->newStatus]}.",
                ['order_id' => $order->id, 'status' => $event->newStatus]
            );
        }
    }
}