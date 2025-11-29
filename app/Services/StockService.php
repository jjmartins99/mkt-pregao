<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockBatch;
use App\Exceptions\InsufficientStockException;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function addStock(Product $product, Warehouse $warehouse, $quantity, $data = [])
    {
        return DB::transaction(function () use ($product, $warehouse, $quantity, $data) {
            // Encontrar ou criar stock
            $stock = Stock::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                ],
                [
                    'min_stock' => $product->min_stock,
                    'max_stock' => $product->max_stock,
                ]
            );

            $oldQuantity = $stock->quantity;
            $newQuantity = $oldQuantity + $quantity;

            $stock->update([
                'quantity' => $newQuantity,
                'available_quantity' => $newQuantity - $stock->reserved_quantity,
            ]);

            // Registrar movimento
            StockMovement::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'type' => 'IN',
                'quantity' => $quantity,
                'balance' => $newQuantity,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'notes' => $data['notes'] ?? 'Entrada de stock',
                'user_id' => $data['user_id'] ?? auth()->id(),
            ]);

            return $stock;
        });
    }

    public function removeStock(Product $product, Warehouse $warehouse, $quantity, $data = [])
    {
        return DB::transaction(function () use ($product, $warehouse, $quantity, $data) {
            $stock = Stock::where('product_id', $product->id)
                         ->where('warehouse_id', $warehouse->id)
                         ->firstOrFail();

            if ($stock->available_quantity < $quantity) {
                throw new InsufficientStockException(
                    "Stock insuficiente para o produto {$product->name}. Disponível: {$stock->available_quantity}, Solicitado: {$quantity}"
                );
            }

            $oldQuantity = $stock->quantity;
            $newQuantity = $oldQuantity - $quantity;

            $stock->update([
                'quantity' => $newQuantity,
                'available_quantity' => $newQuantity - $stock->reserved_quantity,
            ]);

            // Registrar movimento
            StockMovement::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'type' => 'OUT',
                'quantity' => $quantity,
                'balance' => $newQuantity,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'notes' => $data['notes'] ?? 'Saída de stock',
                'user_id' => $data['user_id'] ?? auth()->id(),
            ]);

            return $stock;
        });
    }

    public function transferStock(Product $product, Warehouse $fromWarehouse, Warehouse $toWarehouse, $quantity, $data = [])
    {
        return DB::transaction(function () use ($product, $fromWarehouse, $toWarehouse, $quantity, $data) {
            // Remover do armazém origem
            $this->removeStock($product, $fromWarehouse, $quantity, [
                'reference_type' => 'TRANSFER_OUT',
                'reference_id' => $data['transfer_id'] ?? null,
                'notes' => 'Transferência para ' . $toWarehouse->name,
                'user_id' => $data['user_id'] ?? auth()->id(),
            ]);

            // Adicionar ao armazém destino
            $this->addStock($product, $toWarehouse, $quantity, [
                'reference_type' => 'TRANSFER_IN',
                'reference_id' => $data['transfer_id'] ?? null,
                'notes' => 'Transferência de ' . $fromWarehouse->name,
                'user_id' => $data['user_id'] ?? auth()->id(),
            ]);

            return true;
        });
    }

    public function reserveStock(Product $product, Warehouse $warehouse, $quantity, $referenceType, $referenceId)
    {
        return DB::transaction(function () use ($product, $warehouse, $quantity, $referenceType, $referenceId) {
            $stock = Stock::where('product_id', $product->id)
                         ->where('warehouse_id', $warehouse->id)
                         ->firstOrFail();

            if ($stock->available_quantity < $quantity) {
                throw new InsufficientStockException(
                    "Stock insuficiente para reserva. Disponível: {$stock->available_quantity}, Solicitado: {$quantity}"
                );
            }

            $stock->increment('reserved_quantity', $quantity);
            $stock->decrement('available_quantity', $quantity);

            return $stock;
        });
    }

    public function releaseStock(Product $product, Warehouse $warehouse, $quantity)
    {
        return DB::transaction(function () use ($product, $warehouse, $quantity) {
            $stock = Stock::where('product_id', $product->id)
                         ->where('warehouse_id', $warehouse->id)
                         ->firstOrFail();

            $stock->decrement('reserved_quantity', $quantity);
            $stock->increment('available_quantity', $quantity);

            return $stock;
        });
    }

    public function getAvailableStock(Product $product, Warehouse $warehouse = null)
    {
        if (!$warehouse) {
            // Stock total em todos os armazéns
            return Stock::where('product_id', $product->id)
                       ->sum('available_quantity');
        }

        return Stock::where('product_id', $product->id)
                   ->where('warehouse_id', $warehouse->id)
                   ->value('available_quantity') ?? 0;
    }
}