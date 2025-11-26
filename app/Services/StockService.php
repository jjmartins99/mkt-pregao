<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function addStock(Product $product, Warehouse $warehouse, $quantity, $data = [])
    {
        return DB::transaction(function () use ($product, $warehouse, $quantity, $data) {
            $stock = $product->stocks()
                ->where('warehouse_id', $warehouse->id)
                ->first();

            if ($stock) {
                $stock->increment('quantity', $quantity);
            } else {
                $stock = $product->stocks()->create([
                    'warehouse_id' => $warehouse->id,
                    'quantity' => $quantity,
                    'min_stock' => $product->min_stock,
                    'max_stock' => $product->max_stock,
                ]);
            }

            // Registra movimento de entrada
            StockMovement::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'type' => 'IN',
                'quantity' => $quantity,
                'balance' => $stock->quantity,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            return $stock;
        });
    }

    public function removeStock(Product $product, Warehouse $warehouse, $quantity, $data = [])
    {
        return DB::transaction(function () use ($product, $warehouse, $quantity, $data) {
            $stock = $product->stocks()
                ->where('warehouse_id', $warehouse->id)
                ->firstOrFail();

            if ($stock->quantity < $quantity) {
                throw new \Exception('Stock insuficiente');
            }

            $stock->decrement('quantity', $quantity);

            // Registra movimento de saída
            StockMovement::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouse->id,
                'type' => 'OUT',
                'quantity' => $quantity,
                'balance' => $stock->quantity,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            return $stock;
        });
    }

    public function transferStock(Product $product, $fromWarehouse, $toWarehouse, $quantity, $data = [])
    {
        return DB::transaction(function () use ($product, $fromWarehouse, $toWarehouse, $quantity, $data) {
            // Remove do armazém origem
            $this->removeStock($product, $fromWarehouse, $quantity, [
                'reference_type' => 'TRANSFER',
                'reference_id' => $data['transfer_id'] ?? null,
                'notes' => 'Transferência para ' . $toWarehouse->name,
            ]);

            // Adiciona ao armazém destino
            $this->addStock($product, $toWarehouse, $quantity, [
                'reference_type' => 'TRANSFER',
                'reference_id' => $data['transfer_id'] ?? null,
                'notes' => 'Transferência de ' . $fromWarehouse->name,
            ]);

            return true;
        });
    }
}