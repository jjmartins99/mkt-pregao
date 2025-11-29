<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $cart = Cart::with(['items.product.images', 'items.product.prices', 'store'])
                   ->where('user_id', $user->id)
                   ->first();

        if (!$cart) {
            return response()->json([
                'items' => [],
                'totals' => [
                    'subtotal' => 0,
                    'tax_amount' => 0,
                    'shipping_cost' => 0,
                    'total_amount' => 0
                ]
            ]);
        }

        $cart->calculateTotals();

        return response()->json([
            'items' => $cart->items,
            'totals' => [
                'subtotal' => $cart->subtotal,
                'tax_amount' => $cart->tax_amount,
                'shipping_cost' => $cart->shipping_cost,
                'total_amount' => $cart->total_amount
            ]
        ]);
    }

    public function addItem(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'store_id' => 'required|exists:stores,id',
        ]);

        $user = $request->user();
        $product = Product::with(['prices', 'store'])->findOrFail($request->product_id);

        // Verificar se o produto pertence à loja
        if ($product->store_id != $request->store_id) {
            return response()->json([
                'message' => 'O produto não pertence a esta loja'
            ], 422);
        }

        // Verificar se o produto está ativo
        if (!$product->is_active) {
            return response()->json([
                'message' => 'Produto não disponível'
            ], 400);
        }

        // Verificar stock se for mercadoria
        if ($product->isGood() && $product->track_stock) {
            $availableStock = $product->getCurrentStock($product->store->warehouses->first()->id);
            if ($availableStock < $request->quantity) {
                return response()->json([
                    'message' => 'Stock insuficiente. Disponível: ' . $availableStock
                ], 400);
            }
        }

        return DB::transaction(function () use ($user, $product, $request) {
            // Encontrar ou criar carrinho
            $cart = Cart::firstOrCreate([
                'user_id' => $user->id,
                'store_id' => $request->store_id,
            ]);

            $unitPrice = $product->getCurrentPrice();

            // Verificar se o item já existe no carrinho
            $cartItem = CartItem::where('cart_id', $cart->id)
                               ->where('product_id', $product->id)
                               ->first();

            if ($cartItem) {
                $newQuantity = $cartItem->quantity + $request->quantity;
                
                // Verificar stock novamente para a nova quantidade
                if ($product->isGood() && $product->track_stock) {
                    $availableStock = $product->getCurrentStock($product->store->warehouses->first()->id);
                    if ($availableStock < $newQuantity) {
                        return response()->json([
                            'message' => 'Stock insuficiente. Disponível: ' . $availableStock
                        ], 400);
                    }
                }

                $cartItem->update([
                    'quantity' => $newQuantity,
                    'total_price' => $newQuantity * $unitPrice
                ]);
            } else {
                $cartItem = CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'quantity' => $request->quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $request->quantity * $unitPrice,
                ]);
            }

            $cart->calculateTotals();
            $cart->load(['items.product.images', 'items.product.prices']);

            return response()->json([
                'message' => 'Produto adicionado ao carrinho',
                'cart' => $cart
            ]);
        });
    }

    public function updateItem(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $cartItem = CartItem::with(['product', 'cart'])
                           ->whereHas('cart', function ($query) use ($user) {
                               $query->where('user_id', $user->id);
                           })
                           ->findOrFail($id);

        $product = $cartItem->product;

        // Verificar stock se for mercadoria
        if ($product->isGood() && $product->track_stock) {
            $availableStock = $product->getCurrentStock($product->store->warehouses->first()->id);
            if ($availableStock < $request->quantity) {
                return response()->json([
                    'message' => 'Stock insuficiente. Disponível: ' . $availableStock
                ], 400);
            }
        }

        $cartItem->update([
            'quantity' => $request->quantity,
            'total_price' => $request->quantity * $cartItem->unit_price
        ]);

        $cartItem->cart->calculateTotals();

        return response()->json([
            'message' => 'Item atualizado',
            'item' => $cartItem->fresh('product.images')
        ]);
    }

    public function removeItem(Request $request, $id)
    {
        $user = $request->user();
        $cartItem = CartItem::whereHas('cart', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->findOrFail($id);

        $cart = $cartItem->cart;
        $cartItem->delete();

        $cart->calculateTotals();

        return response()->json([
            'message' => 'Item removido do carrinho',
            'cart' => $cart->fresh(['items.product.images'])
        ]);
    }

    public function clear(Request $request)
    {
        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->first();

        if ($cart) {
            $cart->items()->delete();
            $cart->calculateTotals();
        }

        return response()->json([
            'message' => 'Carrinho limpo'
        ]);
    }

    public function getCartCount(Request $request)
    {
        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->first();

        $count = $cart ? $cart->items->sum('quantity') : 0;

        return response()->json([
            'count' => $count
        ]);
    }
}