<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name'                => ['required', 'string', 'max:255'],
            'category_id'         => ['nullable', 'integer', 'exists:product_categories,id'],
            'sku'                 => ['nullable', 'string', 'max:100'],
            'description'         => ['nullable', 'string'],
            'short_description'   => ['nullable', 'string', 'max:500'],
            'price'               => ['required', 'numeric', 'min:0'],
            'compare_price'       => ['nullable', 'numeric', 'min:0', 'gt:price'],
            'cost_price'          => ['nullable', 'numeric', 'min:0'],
            'stock_quantity'      => ['nullable', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'track_stock'         => ['nullable', 'boolean'],
            'status'              => ['nullable', Rule::in(['draft', 'active', 'archived'])],
            'is_featured'         => ['nullable', 'boolean'],
            'weight'              => ['nullable', 'numeric', 'min:0'],
            'weight_unit'         => ['nullable', Rule::in(['kg', 'g', 'lb', 'oz'])],
            'meta_title'          => ['nullable', 'string', 'max:255'],
            'meta_description'    => ['nullable', 'string', 'max:500'],
            'tag_ids'             => ['nullable', 'array'],
            'tag_ids.*'           => ['integer', 'exists:product_tags,id'],
        ];
    }
}
