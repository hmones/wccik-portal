<?php

namespace App\Http\Requests\Portal;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UploadPaymentProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('applicant')->user()?->activeApplication()?->canSubmitPayment() ?? false;
    }

    public function rules(): array
    {
        return [
            'payment_proof' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'payment_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_proof.required' => 'Please attach your payment proof.',
            'payment_proof.mimes' => 'Payment proof must be a PDF, JPG, or PNG file.',
            'payment_proof.max' => 'Payment proof must be smaller than 5MB.',
        ];
    }
}
