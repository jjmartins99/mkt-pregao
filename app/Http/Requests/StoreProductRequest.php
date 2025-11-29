<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'store_id' => 'required|exists:stores,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sku' => 'required|string|unique:products,sku',
            'barcode' => 'nullable|string',
            'kind' => 'required|in:good,service',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'unit_id' => 'required|exists:units,id',
            'weight' => 'nullable|numeric|min:0',
            'track_stock' => 'boolean',
            'requires_expiry' => 'boolean',
            'requires_batch' => 'boolean',
            'picking_policy' => 'in:fifo,lifo,fefo',
            'min_stock' => 'nullable|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    public function messages()
    {
        return [
            'store_id.required' => 'A loja é obrigatória',
            'name.required' => 'O nome do produto é obrigatório',
            'sku.unique' => 'Este SKU já está em uso',
            'category_id.required' => 'A categoria é obrigatória',
            'price.required' => 'O preço é obrigatório',
        ];
    }
}