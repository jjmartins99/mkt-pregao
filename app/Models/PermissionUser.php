<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PermissionUser extends Pivot
{
    protected $table = 'permission_user';

    protected $fillable = [
        'permission_id',
        'user_id',
    ];

    public $timestamps = true;
}