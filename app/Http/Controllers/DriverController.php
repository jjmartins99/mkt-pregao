<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\Order;
use App\Models\DeliveryTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        $query = Driver::with(['user', 'vehicle', 'company'])
                      ->active()
                      ->verified();

        // Filtros
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('is_verified')) {
            $query->where('is_verified', $request->boolean('is_verified'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $drivers = $query->orderBy('created_at', 'desc')
                        ->paginate($request->get('per_page', 15));

        return response()->json($drivers);
    }

    public function show($id)
    {
        $driver = Driver::with([
            'user',
            'vehicle',
            'company',
            'orders' => function($query) {
                $query->with(['store', 'customer'])->latest()->limit(10);
            }
        ])->findOrFail($id);

        return response()->json($driver);
    }

    public function register(Request $request)
    {
        $user = $request->user();

        // Verificar se o utilizador já é motorista
        if ($user->driverProfile) {
            return response()->json([
                'message' => 'Já tem um perfil de motorista'
            ], 422);
        }

        $request->validate([
            'driving_license' => 'required|string|max:50',
            'license_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'vehicle_type' => 'required|in:car,motorcycle,bicycle,truck,van',
            'vehicle_make' => 'required|string|max:100',
            'vehicle_model' => 'required|string|max:100',
            'vehicle_year' => 'required|integer|min:1900|max:' . date('Y'),
            'vehicle_color' => 'required|string|max:50',
            'vehicle_plate' => 'required|string|max:20|unique:vehicles,plate_number',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        return DB::transaction(function () use ($user, $request) {
            // Upload da foto da carta de condução
            $licensePhotoPath = $request->file('license_photo')->store('drivers/license', 'public');

            // Criar motorista
            $driver = Driver::create([
                'user_id' => $user->id,
                'company_id' => $request->company_id,
                'driving_license' => $request->driving_license,
                'license_photo' => $licensePhotoPath,
                'status' => Driver::STATUS_PENDING,
                'is_verified' => false,
                'is_active' => true,
            ]);

            // Criar veículo
            $driver->vehicle()->create([
                'make' => $request->vehicle_make,
                'model' => $request->vehicle_model,
                'year' => $request->vehicle_year,
                'color' => $request->vehicle_color,
                'plate_number' => $request->vehicle_plate,
                'type' => $request->vehicle_type,
                'is_active' => true,
            ]);

            // Atualizar tipo de utilizador
            $user->update(['type' => 'driver']);

            return response()->json([
                'message' => 'Registro de motorista submetido para aprovação',
                'driver' => $driver->load(['vehicle', 'company'])
            ], 201);
        });
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $driver = $user->driverProfile;

        if (!$driver) {
            return response()->json([
                'message' => 'Perfil de motorista não encontrado'
            ], 404);
        }

        $request->validate([
            'driving_license' => 'sometimes|required|string|max:50',
            'license_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->only(['driving_license']);

        if ($request->hasFile('license_photo')) {
            // Remove foto antiga
            if ($driver->license_photo) {
                Storage::disk('public')->delete($driver->license_photo);
            }
            $data['license_photo'] = $request->file('license_photo')->store('drivers/license', 'public');
        }

        $driver->update($data);

        return response()->json([
            'message' => 'Perfil de motorista atualizado',
            'driver' => $driver->fresh(['vehicle', 'company'])
        ]);
    }

    public function orders(Request $request)
    {
        $user = $request->user();
        $driver = $user->driverProfile;

        if (!$driver) {
            return response()->json([
                'message' => 'Perfil de motorista não encontrado'
            ], 404);
        }

        $query = Order::with(['store', 'customer', 'items.product'])
                     ->where('delivery_driver_id', $driver->id);

        // Filtros
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $orders = $query->orderBy('created_at', 'desc')
                       ->paginate($request->get('per_page', 15));

        return response()->json($orders);
    }

    public function updateOrderStatus(Request $request, $orderId)
    {
        $request->validate([
            'status' => 'required|in:picked_up,on_route,delivered,failed',
            'notes' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'location_address' => 'nullable|string',
        ]);

        $user = $request->user();
        $driver = $user->driverProfile;

        if (!$driver) {
            return response()->json([
                'message' => 'Perfil de motorista não encontrado'
            ], 404);
        }

        $order = Order::where('id', $orderId)
                     ->where('delivery_driver_id', $driver->id)
                     ->firstOrFail();

        $tracking = $order->deliveryTracking;

        if (!$tracking) {
            return response()->json([
                'message' => 'Tracking de entrega não encontrado'
            ], 404);
        }

        // Atualizar tracking
        $tracking->update([
            'status' => $request->status,
            'notes' => $request->notes,
            'status_changed_at' => now(),
        ]);

        // Atualizar localização se fornecida
        if ($request->latitude && $request->longitude) {
            $tracking->updateLocation(
                $request->latitude,
                $request->longitude,
                $request->location_address
            );
        }

        // Atualizar status do pedido se necessário
        if ($request->status === 'delivered') {
            $order->markAsDelivered();
            $driver->incrementDeliveries();
            $driver->addEarnings($order->shipping_cost * 0.8); // 80% para o motorista
        }

        return response()->json([
            'message' => 'Status da entrega atualizado',
            'tracking' => $tracking->fresh()
        ]);
    }

    public function updateLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'location_address' => 'nullable|string',
            'order_id' => 'nullable|exists:orders,id',
        ]);

        $user = $request->user();
        $driver = $user->driverProfile;

        if (!$driver) {
            return response()->json([
                'message' => 'Perfil de motorista não encontrado'
            ], 404);
        }

        // Se order_id fornecido, atualizar tracking específico
        if ($request->order_id) {
            $tracking = DeliveryTracking::where('order_id', $request->order_id)
                                       ->where('driver_id', $driver->id)
                                       ->first();

            if ($tracking) {
                $tracking->updateLocation(
                    $request->latitude,
                    $request->longitude,
                    $request->location_address
                );
            }
        }

        // Também pode guardar a localização atual do motorista
        $driver->update([
            'current_latitude' => $request->latitude,
            'current_longitude' => $request->longitude,
            'last_location_update' => now(),
        ]);

        return response()->json([
            'message' => 'Localização atualizada'
        ]);
    }

    public function getAvailableOrders(Request $request)
    {
        $user = $request->user();
        $driver = $user->driverProfile;

        if (!$driver) {
            return response()->json([
                'message' => 'Perfil de motorista não encontrado'
            ], 404);
        }

        // Pedidos disponíveis para entrega (sem motorista atribuído)
        $orders = Order::with(['store', 'customer'])
                      ->whereNull('delivery_driver_id')
                      ->where('status', 'confirmed')
                      ->whereHas('store', function ($q) use ($driver) {
                          // Filtro por proximidade (simplificado)
                          $q->where('city', 'like', '%' . $driver->user->city . '%');
                      })
                      ->orderBy('created_at', 'asc')
                      ->paginate($request->get('per_page', 10));

        return response()->json($orders);
    }

    public function acceptOrder(Request $request, $orderId)
    {
        $user = $request->user();
        $driver = $user->driverProfile;

        if (!$driver) {
            return response()->json([
                'message' => 'Perfil de motorista não encontrado'
            ], 404);
        }

        $order = Order::whereNull('delivery_driver_id')
                     ->where('status', 'confirmed')
                     ->findOrFail($orderId);

        $order->update([
            'delivery_driver_id' => $driver->id,
            'status' => 'processing',
        ]);

        // Criar tracking
        $order->deliveryTracking()->create([
            'driver_id' => $driver->id,
            'status' => 'assigned',
            'status_changed_at' => now(),
        ]);

        // Adicionar histórico
        $order->addStatusHistory('assigned', 'Motorista aceitou a entrega: ' . $user->name);

        return response()->json([
            'message' => 'Pedido aceite com sucesso',
            'order' => $order->fresh(['store', 'customer'])
        ]);
    }

    public function toggleVerification($id)
    {
        $driver = Driver::findOrFail($id);
        $driver->update([
            'is_verified' => !$driver->is_verified,
            'status' => $driver->is_verified ? Driver::STATUS_ACTIVE : Driver::STATUS_PENDING
        ]);

        return response()->json([
            'message' => 'Estado de verificação do motorista atualizado',
            'driver' => $driver->fresh()
        ]);
    }

    public function getDriverStats(Request $request)
    {
        $user = $request->user();
        $driver = $user->driverProfile;

        if (!$driver) {
            return response()->json([
                'message' => 'Perfil de motorista não encontrado'
            ], 404);
        }

        $stats = [
            'total_deliveries' => $driver->total_deliveries,
            'total_earnings' => $driver->total_earnings,
            'current_rating' => $driver->rating,
            'active_orders' => $driver->orders()->whereIn('status', ['processing', 'shipped'])->count(),
            'completed_today' => $driver->orders()
                                      ->where('status', 'delivered')
                                      ->whereDate('delivered_at', today())
                                      ->count(),
        ];

        return response()->json($stats);
    }
}