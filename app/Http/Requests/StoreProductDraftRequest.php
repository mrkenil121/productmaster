<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Adjust authorization logic as needed
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'manufacturer' => 'required|string|max:255',
            'mrp' => 'required|numeric|min:0',
            'sales_price' => 'required|numeric|min:0|lte:mrp',
            'category_id' => 'required|exists:categories,id',
            'combination' => 'sometimes|unique:products_draft,combination',
            'publish_status' => [
                'sometimes', 
                Rule::in(['draft', 'published', 'unpublished'])
            ],
            'is_banned' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'is_discontinued' => 'sometimes|boolean',
            'is_assured' => 'sometimes|boolean',
            'is_refrigerated' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'sales_price.lte' => 'Sales price cannot be higher than MRP',
            'category_id.exists' => 'Selected category does not exist',
        ];
    }
}

class UpdateProductDraftRequest extends StoreProductDraftRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        
        // Remove unique validation for combination during update
        unset($rules['combination']);
        
        return $rules;
    }
}