<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DriverController extends Controller
{
    public function orders(Request $request)
    {
        $user = $request->user();
        $driver = $user->driverProfile;

        $orders = Order::with(['store', 'customer', 'items.product'])
            ->where('delivery_driver_id', $driver->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($orders);
    }

    public function updateOrderStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:shipped,delivered',
            'current_location' => 'nullable|array',
        ]);

        $user = $request->user();
        $driver = $user->driverProfile;

        $order = Order::where('id', $id)
            ->where('delivery_driver_id', $driver->id)
            ->firstOrFail();

        // Atualiza status
        $order->update(['status' => $request->status]);

        // Se entregue, marca tempo de entrega
        if ($request->status === 'delivered') {
            $order->update(['delivered_at' => now()]);
        }

        // Atualiza localização se fornecida
        if ($request->current_location) {
            // Aqui pode-se implementar tracking de localização
            // para mostrar no mapa em tempo real
        }

        $order->load(['store', 'customer', 'items.product']);

        return response()->json([
            'message' => 'Status do pedido atualizado',
            'order' => $order
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'driving_license' => 'required|string|max:50',
            'license_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'vehicle_type' => 'required|in:car,motorcycle,bicycle,truck',
            'vehicle_model' => 'required|string|max:100',
            'vehicle_plate' => 'required|string|max:20',
            'vehicle_color' => 'required|string|max:50',
            'is_company_driver' => 'boolean',
            'company_id' => 'required_if:is_company_driver,true|exists:companies,id',
        ]);

        $user = $request->user();

        // Upload da foto da carta de condução
        $licensePhotoPath = $request->file('license_photo')->store('drivers/license', 'public');

        $driver = Driver::create([
            'user_id' => $user->id,
            'driving_license' => $request->driving_license,
            'license_photo' => $licensePhotoPath,
            'is_verified' => false, // Precisa de verificação administrativa
            'is_active' => true,
            'rating' => 0,
            'total_deliveries' => 0,
        ]);

        // Cria veículo
        $driver->vehicle()->create([
            'type' => $request->vehicle_type,
            'model' => $request->vehicle_model,
            'plate' => $request->vehicle_plate,
            'color' => $request->vehicle_color,
            'is_active' => true,
        ]);

        $user->update(['type' => 'driver']);
        $driver->load('vehicle');

        return response()->json([
            'message' => 'Registro de motorista submetido para aprovação',
            'driver' => $driver
        ], 201);
    }
}