<?php

namespace App\Http\Requests\Admin;

use App\Contracts\Repositories\SettingRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property string $gateway
 * [AI] Directive 57326: V1 authorised gateway = Paystack only.
 * All other gateway validation branches removed.
 */
class PaymentMethodUpdateRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function __construct(
        private readonly SettingRepositoryInterface $settingRepo,
    ) {}

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $validationRules = [
            'gateway' => ['required', Rule::in(['paystack'])],
            'mode'    => 'required|in:live,test',
        ];
        $additionalDataRules = $this->getAdditionalDataRules();
        return array_merge($validationRules, $additionalDataRules);
    }

    public function messages(): array
    {
        return [
            'gateway.required'       => translate('the_gateway_field_is_required'),
            'gateway_title.required' => translate('the_gateway_title_field_is_required'),
            'gateway_image.required' => translate('gateway_image_is_required'),
        ];
    }

    protected function getAdditionalDataRules(): array
    {
        collect(['status'])->each(fn($item, $key) => $this[$item] = $this->has($item) ? (int)$this[$item] : 0);

        $additionalDataRules = [];
        if ($this['gateway'] == 'paystack') {
            $additionalDataRules = [
                'status'           => 'required|in:1,0',
                'public_key'       => 'required',
                'secret_key'       => 'required',
                'merchant_email'   => 'required',
            ];
        }

        return $additionalDataRules;
    }
}
