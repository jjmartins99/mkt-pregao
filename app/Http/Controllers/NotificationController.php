<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Notification::where('user_id', $user->id)
                            ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->has('is_read')) {
            $query->where('is_read', $request->boolean('is_read'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $notifications = $query->paginate($request->get('per_page', 15));

        return response()->json($notifications);
    }

    public function markAsRead($id)
    {
        $user = $request->user();
        $notification = Notification::where('user_id', $user->id)
                                   ->findOrFail($id);

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notificação marcada como lida'
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        
        Notification::where('user_id', $user->id)
                   ->where('is_read', false)
                   ->update([
                       'is_read' => true,
                       'read_at' => now()
                   ]);

        return response()->json([
            'message' => 'Todas as notificações marcadas como lidas'
        ]);
    }

    public function getUnreadCount(Request $request)
    {
        $user = $request->user();
        
        $count = Notification::where('user_id', $user->id)
                            ->where('is_read', false)
                            ->count();

        return response()->json([
            'count' => $count
        ]);
    }

    public function destroy($id)
    {
        $user = $request->user();
        $notification = Notification::where('user_id', $user->id)
                                   ->findOrFail($id);

        $notification->delete();

        return response()->json([
            'message' => 'Notificação eliminada'
        ]);
    }

    public function clearAll(Request $request)
    {
        $user = $request->user();
        
        Notification::where('user_id', $user->id)->delete();

        return response()->json([
            'message' => 'Todas as notificações eliminadas'
        ]);
    }
}