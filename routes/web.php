<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'PREGÃO Marketplace API',
        'version' => '1.0.0',
        'status' => 'online',
        'message' => 'API funcionando corretamente'
    ]);
});
