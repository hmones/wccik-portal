<?php

namespace App\Http\Requests\Portal;

use App\Enums\CompanyClassification;
use App\Enums\Industry;
use App\Enums\MembershipClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Required by Annex 1, pick exactly one on both.
            'membership_class' => ['required', Rule::in(array_column(MembershipClass::cases(), 'value'))],
            'industry' => ['required', Rule::in(array_column(Industry::cases(), 'value'))],

            'authorized_representative_name' => ['required', 'string', 'max:255'],
            'cnic' => ['required', 'string', 'regex:/^\d{5}-\d{7}-\d{1}$/'],
            'cnic_expiry_date' => ['nullable', 'date'],
            'company_name' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'established_year' => ['nullable', 'integer', 'min:1900', 'max:'.(int) date('Y')],
            'company_classification' => ['required', Rule::in(array_column(CompanyClassification::cases(), 'value'))],

            // Financial / tax snapshot (optional in the printed form too)
            'turnover_pkr' => ['nullable', 'integer', 'min:0'],
            'employees_count' => ['nullable', 'integer', 'min:0'],
            'has_ntn' => ['required', 'boolean'],
            'ntn_number' => ['nullable', 'required_if:has_ntn,true', 'string', 'max:20'],
            'sales_tax_no' => ['nullable', 'string', 'max:30'],

            // Address
            'address' => ['required', 'string', 'max:1000'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'district' => ['required', 'string', 'max:100'],

            // Contact
            'phone' => ['nullable', 'string', 'max:30'],
            'cell' => ['required', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'alternate_no' => ['nullable', 'string', 'max:30'],

            'other_chamber_memberships' => ['nullable', 'string', 'max:500'],

            // Payment is supplied from the dashboard only after form acceptance.
            'payment_proof' => ['prohibited'],
            'payment_date' => ['prohibited'],
            'payment_method' => ['prohibited'],
            'terms_confirmed' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'cnic.regex' => 'CNIC must be in the format: 12345-1234567-1',
            'terms_confirmed.accepted' => 'You must confirm you understand the 10-day document submission requirement.',
            'ntn_number.required_if' => 'NTN is required when you indicate you are registered with FBR.',
            'payment_proof.mimes' => 'Payment proof must be a PDF, JPG, or PNG file.',
            'payment_proof.max' => 'Payment proof must be smaller than 5MB.',
        ];
    }
}
