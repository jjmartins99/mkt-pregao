<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Support\Str;

class PaymentService
{
    public function processPayment(Order $order, $paymentMethod, $paymentData = [])
    {
        // Simular processamento de pagamento
        $transaction = Transaction::create([
            'transaction_number' => 'TXN' . Str::upper(Str::random(10)),
            'order_id' => $order->id,
            'user_id' => $order->customer_id,
            'type' => Transaction::TYPE_SALE,
            'status' => Transaction::STATUS_PENDING,
            'amount' => $order->total_amount,
            'fee' => $this->calculatePaymentFee($order->total_amount, $paymentMethod),
            'net_amount' => $order->total_amount - $this->calculatePaymentFee($order->total_amount, $paymentMethod),
            'currency' => 'AOA',
            'payment_gateway' => $paymentMethod,
            'description' => 'Pagamento do pedido ' . $order->order_number,
        ]);

        // Simular processamento (em produção integraria com gateway real)
        if ($this->simulatePaymentProcessing($paymentMethod)) {
            $transaction->markAsCompleted();
            $order->markAsPaid();

            return [
                'success' => true,
                'transaction' => $transaction,
                'message' => 'Pagamento processado com sucesso'
            ];
        } else {
            $transaction->markAsFailed();
            
            return [
                'success' => false,
                'transaction' => $transaction,
                'message' => 'Falha no processamento do pagamento'
            ];
        }
    }

    private function calculatePaymentFee($amount, $paymentMethod)
    {
        $fees = [
            'cash' => 0,
            'card' => $amount * 0.025, // 2.5%
            'transfer' => $amount * 0.015, // 1.5%
            'mobile' => $amount * 0.02, // 2%
        ];

        return $fees[$paymentMethod] ?? 0;
    }

    private function simulatePaymentProcessing($paymentMethod)
    {
        // Simulação - em produção seria integração real com gateway
        $successRate = [
            'cash' => 1.0,
            'card' => 0.95,
            'transfer' => 0.98,
            'mobile' => 0.92,
        ];

        return rand(0, 100) < ($successRate[$paymentMethod] * 100);
    }

    public function refundPayment(Order $order, $amount = null)
    {
        $refundAmount = $amount ?? $order->total_amount;

        $transaction = Transaction::create([
            'transaction_number' => 'RFN' . Str::upper(Str::random(10)),
            'order_id' => $order->id,
            'user_id' => $order->customer_id,
            'type' => Transaction::TYPE_REFUND,
            'status' => Transaction::STATUS_COMPLETED,
            'amount' => $refundAmount,
            'fee' => 0,
            'net_amount' => $refundAmount,
            'currency' => 'AOA',
            'payment_gateway' => $order->payment_method,
            'description' => 'Reembolso do pedido ' . $order->order_number,
            'processed_at' => now(),
        ]);

        // Atualizar status do pedido
        if ($refundAmount == $order->total_amount) {
            $order->update(['payment_status' => Order::PAYMENT_STATUS_REFUNDED]);
        } else {
            $order->update(['payment_status' => Order::PAYMENT_STATUS_PARTIALLY_REFUNDED]);
        }

        return $transaction;
    }
}