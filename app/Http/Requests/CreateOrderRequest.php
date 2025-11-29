<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'store_id' => 'required|exists:stores,id',
            'payment_method' => 'required|in:cash,card,transfer,mobile',
            'delivery_address' => 'required|array',
            'delivery_address.street' => 'required|string|max:255',
            'delivery_address.city' => 'required|string|max:100',
            'delivery_address.province' => 'required|string|max:100',
            'delivery_address.postal_code' => 'required|string|max:20',
            'delivery_address.country' => 'required|string|max:100',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages()
    {
        return [
            'delivery_address.required' => 'O endereço de entrega é obrigatório',
            'delivery_address.street.required' => 'A rua é obrigatória',
            'delivery_address.city.required' => 'A cidade é obrigatória',
            'payment_method.required' => 'O método de pagamento é obrigatório',
        ];
    }
}