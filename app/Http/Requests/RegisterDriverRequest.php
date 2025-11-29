<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterDriverRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'driving_license' => 'required|string|max:50',
            'license_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'vehicle_type' => 'required|in:car,motorcycle,bicycle,truck,van',
            'vehicle_make' => 'required|string|max:100',
            'vehicle_model' => 'required|string|max:100',
            'vehicle_year' => 'required|integer|min:1900|max:' . date('Y'),
            'vehicle_color' => 'required|string|max:50',
            'vehicle_plate' => 'required|string|max:20|unique:vehicles,plate_number',
            'company_id' => 'nullable|exists:companies,id',
        ];
    }

    public function messages()
    {
        return [
            'license_photo.required' => 'A foto da carta de condução é obrigatória',
            'vehicle_plate.unique' => 'Esta matrícula já está registada',
            'driving_license.required' => 'O número da carta de condução é obrigatório',
        ];
    }
}