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
            'publish_status' => [
                'sometimes', 
                Rule::in(['draft', 'published', 'unpublished'])
            ],
            'is_banned' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'is_discontinued' => 'sometimes|boolean',
            'is_assured' => 'sometimes|boolean',
            'is_refrigerated' => 'sometimes|boolean',
            'molecule_ids' => [
                'sometimes', 
                'array', 
                function ($attribute, $value, $fail) {
                    // If it's a string, convert to array
                    if (is_string($value)) {
                        $value = array_map('trim', explode(',', $value));
                    }

                    // Validate each molecule ID
                    foreach ($value as $moleculeId) {
                        if (!is_numeric($moleculeId)) {
                            $fail("Each $attribute must be a numeric ID");
                        }
                    }
                }
            ],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        // Convert comma-separated molecule_ids to array if needed
        if (isset($data['molecule_ids']) && is_string($data['molecule_ids'])) {
            $data['molecule_ids'] = array_map('trim', explode(',', $data['molecule_ids']));
        }

        return $data;
    }

    public function messages(): array
    {
        return [
            'sales_price.lte' => 'Sales price cannot be higher than MRP',
            'category_id.exists' => 'Selected category does not exist',
        ];
    }
}

