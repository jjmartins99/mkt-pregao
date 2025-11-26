<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    // Relações
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Escopos
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Métodos
    public function markAsRead()
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    public function markAsUnread()
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    public static function createNotification($userId, $type, $title, $message, $data = null)
    {
        return self::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'is_read' => false,
        ]);
    }

    public function getNotificationIconAttribute()
    {
        $icons = [
            'order_created' => '🛒',
            'order_status_changed' => '📦',
            'payment_received' => '💰',
            'delivery_assigned' => '🚚',
            'review_received' => '⭐',
            'system_alert' => '⚠️',
            'promotion' => '🎁',
        ];

        return $icons[$this->type] ?? '📢';
    }

    public function getFormattedDateAttribute()
    {
        return $this->created_at->diffForHumans();
    }
}