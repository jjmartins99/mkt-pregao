<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    protected $product;
    protected $available;
    protected $requested;

    public function __construct($message = "", $code = 0, Exception $previous = null, $product = null, $available = 0, $requested = 0)
    {
        parent::__construct($message, $code, $previous);
        
        $this->product = $product;
        $this->available = $available;
        $this->requested = $requested;
    }

    public function getProduct()
    {
        return $this->product;
    }

    public function getAvailable()
    {
        return $this->available;
    }

    public function getRequested()
    {
        return $this->requested;
    }

    public function render($request)
    {
        return response()->json([
            'message' => $this->getMessage(),
            'product' => $this->product ? $this->product->only(['id', 'name', 'sku']) : null,
            'available' => $this->available,
            'requested' => $this->requested,
        ], 422);
    }
}