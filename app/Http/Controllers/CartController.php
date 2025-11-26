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
        $cart = Cart::with(['items.product.images', 'items.product.store'])
            ->where('user_id', $user->id)
            ->first();

        if (!$cart) {
            return response()->json(['items' => [], 'total' => 0]);
        }

        return response()->json([
            'items' => $cart->items,
            'total' => $cart->total
        ]);
    }

    public function addItem(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $product = Product::with('prices')->findOrFail($request->product_id);

        // Verifica se o produto está ativo
        if (!$product->is_active) {
            return response()->json(['message' => 'Produto não disponível'], 400);
        }

        // Verifica stock se for mercadoria
        if ($product->isGood() && $product->track_stock) {
            $availableStock = $product->getCurrentStock($product->store->warehouses->first()->id);
            if ($availableStock < $request->quantity) {
                return response()->json([
                    'message' => 'Stock insuficiente. Disponível: ' . $availableStock
                ], 400);
            }
        }

        return DB::transaction(function () use ($user, $product, $request) {
            $cart = Cart::firstOrCreate(['user_id' => $user->id]);

            $unitPrice = $product->prices()->first()->price;

            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->first();

            if ($cartItem) {
                $cartItem->increment('quantity', $request->quantity);
            } else {
                $cartItem = CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'quantity' => $request->quantity,
                    'unit_price' => $unitPrice,
                ]);
            }

            $cart->load('items.product');

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
        $cartItem = CartItem::with('product', 'cart')
            ->whereHas('cart', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->findOrFail($id);

        // Verifica stock se for mercadoria
        if ($cartItem->product->isGood() && $cartItem->product->track_stock) {
            $availableStock = $cartItem->product->getCurrentStock(
                $cartItem->product->store->warehouses->first()->id
            );
            if ($availableStock < $request->quantity) {
                return response()->json([
                    'message' => 'Stock insuficiente. Disponível: ' . $availableStock
                ], 400);
            }
        }

        $cartItem->update(['quantity' => $request->quantity]);

        return response()->json([
            'message' => 'Item atualizado',
            'item' => $cartItem
        ]);
    }

    public function removeItem(Request $request, $id)
    {
        $user = $request->user();
        $cartItem = CartItem::whereHas('cart', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->findOrFail($id);

        $cartItem->delete();

        return response()->json(['message' => 'Item removido do carrinho']);
    }
}