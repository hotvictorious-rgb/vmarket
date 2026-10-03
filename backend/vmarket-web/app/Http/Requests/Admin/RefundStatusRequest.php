<?php

namespace App\Http\Requests\Admin;

use App\Traits\ResponseHandler;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RefundStatusRequest extends FormRequest
{
    use ResponseHandler;
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'id' => 'required',
            'refund_status' => 'required|in:pending,approved,rejected,refunded',
            'approved_note' => $this->input('refund_status') == 'approved' ? 'required' : '',
            'rejected_note' => $this->input('refund_status') == 'rejected' ? 'required' : '',
            'payment_method' => $this->input('refund_status') == 'refunded' ? 'required' : '',
            'amount' => $this->input('refund_status') == 'refunded' ? 'required|numeric|min:0.01' : 'nullable|numeric',
            'payment_reference' => $this->input('refund_status') == 'refunded' ? 'required|string|max:255' : 'nullable|string',
            'payment_date' => $this->input('refund_status') == 'refunded' ? 'required|date' : 'nullable|date',
            'payment_evidence' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }
    public function messages(): array
    {
        return [
            'approved_note.required' => translate('The_approved_note_field_is_required'),
            'rejected_note.required' => translate('The_rejected_note_field_is_required'),
            'payment_method.required' => translate('The_payment_method_field_is_required'),
            'amount.required' => translate('The_transferred_amount_is_required'),
            'payment_reference.required' => translate('The_payment_reference_field_is_required'),
            'payment_date.required' => translate('The_payment_date_field_is_required'),
        ];
    }
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json(['errors' => $this->errorProcessor($validator)]));
    }
}
