<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\Driver;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('check.user.type:admin');
    }

    public function dashboardStats()
    {
        $stats = [
            'total_users' => User::count(),
            'total_stores' => Store::count(),
            'total_products' => Product::count(),
            'total_orders' => Order::count(),
            'total_drivers' => Driver::verified()->active()->count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'today_orders' => Order::whereDate('created_at', today())->count(),
            'total_revenue' => Order::where('status', 'delivered')->sum('total_amount'),
        ];

        return response()->json($stats);
    }

    public function recentActivities()
    {
        $activities = [
            'recent_orders' => Order::with(['store', 'customer'])
                                   ->orderBy('created_at', 'desc')
                                   ->limit(10)
                                   ->get(),
            'recent_users' => User::with(['stores'])
                                 ->orderBy('created_at', 'desc')
                                 ->limit(10)
                                 ->get(),
            'recent_stores' => Store::with(['owner'])
                                   ->orderBy('created_at', 'desc')
                                   ->limit(10)
                                   ->get(),
        ];

        return response()->json($activities);
    }

    public function salesReport(Request $request)
    {
        $request->validate([
            'period' => 'required|in:today,week,month,year,custom',
            'start_date' => 'required_if:period,custom|date',
            'end_date' => 'required_if:period,custom|date|after:start_date',
        ]);

        $query = Order::where('status', 'delivered');

        switch ($request->period) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
                break;
            case 'year':
                $query->whereBetween('created_at', [now()->startOfYear(), now()->endOfYear()]);
                break;
            case 'custom':
                $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
                break;
        }

        $report = [
            'total_sales' => $query->count(),
            'total_revenue' => $query->sum('total_amount'),
            'average_order_value' => $query->avg('total_amount') ?? 0,
            'top_products' => $this->getTopProducts($request),
            'sales_by_day' => $this->getSalesByDay($request),
        ];

        return response()->json($report);
    }

    private function getTopProducts(Request $request)
    {
        return DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->where('orders.status', 'delivered')
                ->when($request->period === 'today', function ($q) {
                    $q->whereDate('orders.created_at', today());
                })
                ->when($request->period === 'week', function ($q) {
                    $q->whereBetween('orders.created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                })
                ->when($request->period === 'month', function ($q) {
                    $q->whereBetween('orders.created_at', [now()->startOfMonth(), now()->endOfMonth()]);
                })
                ->when($request->period === 'year', function ($q) {
                    $q->whereBetween('orders.created_at', [now()->startOfYear(), now()->endOfYear()]);
                })
                ->when($request->period === 'custom', function ($q) use ($request) {
                    $q->whereBetween('orders.created_at', [$request->start_date, $request->end_date]);
                })
                ->select(
                    'products.name',
                    'products.sku',
                    DB::raw('SUM(order_items.quantity) as total_sold'),
                    DB::raw('SUM(order_items.total_price) as total_revenue')
                )
                ->groupBy('products.id', 'products.name', 'products.sku')
                ->orderBy('total_sold', 'desc')
                ->limit(10)
                ->get();
    }

    private function getSalesByDay(Request $request)
    {
        return DB::table('orders')
                ->where('status', 'delivered')
                ->when($request->period === 'today', function ($q) {
                    $q->whereDate('created_at', today());
                })
                ->when($request->period === 'week', function ($q) {
                    $q->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                })
                ->when($request->period === 'month', function ($q) {
                    $q->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
                })
                ->when($request->period === 'year', function ($q) {
                    $q->whereBetween('created_at', [now()->startOfYear(), now()->endOfYear()]);
                })
                ->when($request->period === 'custom', function ($q) use ($request) {
                    $q->whereBetween('created_at', [$request->start_date, $request->end_date]);
                })
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as orders'),
                    DB::raw('SUM(total_amount) as revenue')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();
    }

    public function userManagement(Request $request)
    {
        $query = User::with(['stores', 'driverProfile']);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $users = $query->orderBy('created_at', 'desc')
                      ->paginate($request->get('per_page', 15));

        return response()->json($users);
    }

    public function storeManagement(Request $request)
    {
        $query = Store::with(['owner', 'company']);

        if ($request->has('is_verified')) {
            $query->where('is_verified', $request->boolean('is_verified'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $stores = $query->orderBy('created_at', 'desc')
                       ->paginate($request->get('per_page', 15));

        return response()->json($stores);
    }

    public function driverManagement(Request $request)
    {
        $query = Driver::with(['user', 'vehicle', 'company']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('is_verified')) {
            $query->where('is_verified', $request->boolean('is_verified'));
        }

        $drivers = $query->orderBy('created_at', 'desc')
                        ->paginate($request->get('per_page', 15));

        return response()->json($drivers);
    }
}