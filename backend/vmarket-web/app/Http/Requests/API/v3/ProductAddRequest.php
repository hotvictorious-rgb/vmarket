<?php

namespace App\Http\Requests\API\v3;

use App\Traits\ResponseHandler;
use Illuminate\Foundation\Http\FormRequest;
use App\Rules\DisallowedExtension;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\UploadedFile;
use Modules\TaxModule\app\Traits\VatTaxManagement;

class ProductAddRequest extends FormRequest
{
    use ResponseHandler;
    use VatTaxManagement;
    public function authorize(): bool
    {
        return true;
    }
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'category_id' => 'required|exists:categories,id,position,0',
            'unit_price' => 'required|numeric|gt:0',
            'images' => 'required',
            'code' => 'nullable|string|max:50',
            'thumbnail' => 'nullable',
            'lang' => 'nullable',
            'product_type' => 'nullable|string',
            'unit' => 'nullable|string',
            'minimum_order_qty' => 'nullable|numeric|min:1',
            'shipping_cost' => 'nullable|numeric',
        ];
    }
    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => translate('Product name is required!'),
            'category_id.required' => translate('category is required!'),
            'category_id.exists' => translate('Selected category does not exist!'),
            'unit_price.required' => translate('Product price is required!'),
            'unit_price.gt' => translate('Product price must be greater than zero!'),
            'images.required' => translate('Product images is required!'),
        ];
    }
    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // [AI] Strict 1 to 5 images bound enforcement
            $images = is_array($this->images) ? $this->images : json_decode($this->images, true);
            if (!is_array($images) || count($images) < 1) {
                $validator->errors()->add('images', translate('Minimum 1 product image is required!'));
            } elseif (count($images) > 5) {
                $validator->errors()->add('images', translate('Maximum 5 product images are allowed!'));
            }

            if ($this->preview_file) {
                $disallowedExtensions = ['php', 'java', 'js', 'html', 'exe', 'sh'];
                $maxFileSize = 10 * 1024 * 1024; // 10 MB in bytes
                $extension = $this->preview_file->getClientOriginalExtension();
                $fileSize = $this->preview_file->getSize();

                if ($fileSize > $maxFileSize) {
                    $validator->errors()->add('files', translate('File_size_exceeds_the_maximum_limit_of_10MB') . '!');
                } elseif (in_array($extension, $disallowedExtensions)) {
                    $validator->errors()->add('files', translate('Files_with_extensions_like') . (' .php,.java,.js,.html,.exe,.sh ') . translate('are_not_supported') . '!');
                }
            }
        });
    }
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'errors' => $this->errorProcessor($validator),
            ], 403)
        );
    }
}
