<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';

    protected $fillable = [
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'description',
        'user_id',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // Relações
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function model()
    {
        return $this->morphTo();
    }

    // Escopos
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByModel($query, $modelType, $modelId = null)
    {
        $query = $query->where('model_type', $modelType);
        
        if ($modelId) {
            $query->where('model_id', $modelId);
        }
        
        return $query;
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Métodos
    public static function log($action, $model, $oldValues = null, $newValues = null, $description = null, $userId = null)
    {
        return self::create([
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
            'user_id' => $userId ?? auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function getChangesAttribute()
    {
        if (!$this->old_values || !$this->new_values) {
            return [];
        }

        $changes = [];
        foreach ($this->new_values as $key => $newValue) {
            $oldValue = $this->old_values[$key] ?? null;
            
            if ($oldValue != $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }

    public function hasChanges()
    {
        return !empty($this->changes);
    }

    public function getModelNameAttribute()
    {
        return class_basename($this->model_type);
    }

    public function getFormattedActionAttribute()
    {
        $actions = [
            'created' => 'Criado',
            'updated' => 'Atualizado',
            'deleted' => 'Eliminado',
            'restored' => 'Restaurado',
            'login' => 'Login',
            'logout' => 'Logout',
        ];

        return $actions[$this->action] ?? $this->action;
    }

    public function getFormattedDateAttribute()
    {
        return $this->created_at->format('d/m/Y H:i:s');
    }
}