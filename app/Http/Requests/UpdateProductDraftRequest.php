<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductDraftRequest extends StoreProductDraftRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['combination']); // Removing unique validation for combination
        return $rules;
    }
}
