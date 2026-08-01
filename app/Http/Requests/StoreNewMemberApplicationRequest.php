<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNewMemberApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'website' => ['nullable', 'string', 'max:255'],
            'authorized_representative_name' => ['required', 'string', 'max:255'],
            'cnic' => ['required', 'string', 'regex:/^\d{5}-\d{7}-\d{1}$/'],
            'company_name' => ['required', 'string', 'max:255'],
            'company_classification' => ['required', 'string', 'in:proprietorship,partnership,private_ltd,public_ltd,aop'],
            'address' => ['required', 'string', 'max:1000'],
            'district' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'cell' => ['required', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255'],
            'has_ntn' => ['required', 'boolean'],
            'ntn_number' => ['nullable', 'required_if:has_ntn,true', 'string', 'max:20'],
            'captcha_answer' => ['required', 'integer', function ($attribute, $value, $fail) {
                if ((int) $value !== (int) session('captcha_answer')) {
                    $fail('The security answer is incorrect.');
                }
            }],
            'confirm_10_days' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'cnic.regex' => 'CNIC must be in the format: 12345-1234567-1',
            'confirm_10_days.accepted' => 'You must confirm that you understand the 10-day document submission requirement.',
            'captcha_answer.required' => 'Please answer the security question.',
            'ntn_number.required_if' => 'NTN is required when you indicate you are registered with FBR.',
        ];
    }
}
