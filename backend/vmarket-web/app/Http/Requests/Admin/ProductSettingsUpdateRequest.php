<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProductSettingsUpdateRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stock_limit' => 'sometimes|integer|min:0',
            'product_edit_approval_mode' => 'nullable|in:auto,threshold,strict',
            'product_edit_price_threshold_percentage' => 'nullable|numeric|min:0|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'stock_limit.integer' => translate('The_stock_limit_must_be_an_integer'),
            'stock_limit.min' => translate('The_stock_limit_must_be_at_least_0'),
            'product_edit_approval_mode.in' => translate('Invalid_product_edit_approval_mode'),
            'product_edit_price_threshold_percentage.numeric' => translate('Threshold_must_be_a_valid_number'),
        ];
    }
}
