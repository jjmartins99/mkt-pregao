<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    // Escopos
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByGroup($query, $group)
    {
        return $query->where('group', $group);
    }

    public function scopeGeneral($query)
    {
        return $query->where('group', 'general');
    }

    public function scopePayment($query)
    {
        return $query->where('group', 'payment');
    }

    public function scopeShipping($query)
    {
        return $query->where('group', 'shipping');
    }

    public function scopeTax($query)
    {
        return $query->where('group', 'tax');
    }

    // Métodos
    public function getValueAttribute($value)
    {
        switch ($this->type) {
            case 'boolean':
                return (bool) $value;
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'array':
            case 'json':
                return json_decode($value, true) ?? [];
            default:
                return $value;
        }
    }

    public function setValueAttribute($value)
    {
        if ($this->type === 'array' || $this->type === 'json') {
            $this->attributes['value'] = json_encode($value);
        } else {
            $this->attributes['value'] = $value;
        }
    }

    public static function getValue($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function setValue($key, $value, $type = 'string', $group = 'general', $description = null, $isPublic = false)
    {
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'group' => $group,
                'description' => $description,
                'is_public' => $isPublic,
            ]
        );
    }

    public static function getGroupSettings($group)
    {
        return self::byGroup($group)->get()->pluck('value', 'key')->toArray();
    }

    public function isEditable()
    {
        return !in_array($this->key, [
            'app_name',
            'app_version',
            'system_initialized',
        ]);
    }
}
