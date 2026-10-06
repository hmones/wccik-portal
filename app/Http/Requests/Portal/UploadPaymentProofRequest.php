<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class UploadPaymentProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_proof' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
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
