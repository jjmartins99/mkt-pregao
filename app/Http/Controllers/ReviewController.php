<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Order;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with(['user', 'product', 'store', 'driver', 'order'])
                      ->approved()
                      ->visible();

        // Filtros
        if ($request->has('type')) {
            switch ($request->type) {
                case 'product':
                    $query->whereNotNull('product_id');
                    break;
                case 'store':
                    $query->whereNotNull('store_id');
                    break;
                case 'driver':
                    $query->whereNotNull('driver_id');
                    break;
            }
        }

        if ($request->has('rating')) {
            $query->where('rating', $request->rating);
        }

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('has_response')) {
            $query->whereNotNull('response');
        }

        $reviews = $query->orderBy('created_at', 'desc')
                        ->paginate($request->get('per_page', 15));

        return response()->json($reviews);
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'product_id' => 'nullable|required_without_all:store_id,driver_id|exists:products,id',
            'store_id' => 'nullable|required_without_all:product_id,driver_id|exists:stores,id',
            'driver_id' => 'nullable|required_without_all:product_id,store_id|exists:drivers,id',
        ]);

        $user = $request->user();
        $order = Order::findOrFail($request->order_id);

        // Verificar se o pedido pertence ao utilizador
        if ($order->customer_id !== $user->id) {
            return response()->json([
                'message' => 'Não tem permissão para avaliar este pedido'
            ], 403);
        }

        // Verificar se o pedido foi entregue
        if (!$order->isDelivered()) {
            return response()->json([
                'message' => 'Só pode avaliar pedidos entregues'
            ], 422);
        }

        // Verificar se já existe uma avaliação para este item
        $existingReview = Review::where('order_id', $order->id)
                               ->where('user_id', $user->id)
                               ->when($request->product_id, function ($q) use ($request) {
                                   $q->where('product_id', $request->product_id);
                               })
                               ->when($request->store_id, function ($q) use ($request) {
                                   $q->where('store_id', $request->store_id);
                               })
                               ->when($request->driver_id, function ($q) use ($request) {
                                   $q->where('driver_id', $request->driver_id);
                               })
                               ->first();

        if ($existingReview) {
            return response()->json([
                'message' => 'Já avaliou este item'
            ], 422);
        }

        $review = Review::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'product_id' => $request->product_id,
            'store_id' => $request->store_id,
            'driver_id' => $request->driver_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'is_approved' => true, // Aprovação automática para clientes
            'is_visible' => true,
        ]);

        return response()->json([
            'message' => 'Avaliação submetida com sucesso',
            'review' => $review->load(['user', 'product', 'store', 'driver'])
        ], 201);
    }

    public function addResponse(Request $request, $id)
    {
        $request->validate([
            'response' => 'required|string|max:1000',
        ]);

        $review = Review::findOrFail($id);
        $user = $request->user();

        // Verificar permissões para responder
        if (!$this->canRespondToReview($user, $review)) {
            return response()->json([
                'message' => 'Não tem permissão para responder a esta avaliação'
            ], 403);
        }

        $review->addResponse($request->response, $user->id);

        return response()->json([
            'message' => 'Resposta adicionada com sucesso',
            'review' => $review->fresh(['user', 'product', 'store', 'driver'])
        ]);
    }

    public function approveReview($id)
    {
        $review = Review::findOrFail($id);
        $review->approve();

        return response()->json([
            'message' => 'Avaliação aprovada com sucesso',
            'review' => $review->fresh()
        ]);
    }

    public function rejectReview($id)
    {
        $review = Review::findOrFail($id);
        $review->reject();

        return response()->json([
            'message' => 'Avaliação rejeitada',
            'review' => $review->fresh()
        ]);
    }

    public function getPendingReviews(Request $request)
    {
        $query = Review::with(['user', 'product', 'store', 'driver'])
                      ->where('is_approved', false)
                      ->orderBy('created_at', 'desc');

        $reviews = $query->paginate($request->get('per_page', 15));

        return response()->json($reviews);
    }

    public function getProductReviews($productId)
    {
        $reviews = Review::with(['user'])
                        ->where('product_id', $productId)
                        ->approved()
                        ->visible()
                        ->orderBy('created_at', 'desc')
                        ->paginate(10);

        $stats = [
            'average_rating' => $reviews->avg('rating') ?? 0,
            'total_reviews' => $reviews->total(),
            'rating_distribution' => $this->getRatingDistribution($productId, 'product'),
        ];

        return response()->json([
            'reviews' => $reviews,
            'stats' => $stats
        ]);
    }

    public function getStoreReviews($storeId)
    {
        $reviews = Review::with(['user'])
                        ->where('store_id', $storeId)
                        ->approved()
                        ->visible()
                        ->orderBy('created_at', 'desc')
                        ->paginate(10);

        $stats = [
            'average_rating' => $reviews->avg('rating') ?? 0,
            'total_reviews' => $reviews->total(),
            'rating_distribution' => $this->getRatingDistribution($storeId, 'store'),
        ];

        return response()->json([
            'reviews' => $reviews,
            'stats' => $stats
        ]);
    }

    private function getRatingDistribution($id, $type)
    {
        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        $reviews = Review::where("{$type}_id", $id)
                        ->approved()
                        ->visible()
                        ->get();

        foreach ($reviews as $review) {
            $distribution[$review->rating]++;
        }

        return $distribution;
    }

    private function canRespondToReview($user, $review)
    {
        if ($user->isAdmin()) return true;
        if ($review->store_id && $review->store->owner_id === $user->id) return true;
        if ($review->driver_id && $review->driver->user_id === $user->id) return true;
        
        return false;
    }
}