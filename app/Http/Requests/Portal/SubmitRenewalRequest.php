<?php

namespace App\Http\Requests\Portal;

use App\Enums\CompanyClassification;
use App\Enums\Industry;
use App\Enums\MembershipClass;
use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitRenewalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'membership_class' => ['required', Rule::in(array_column(MembershipClass::cases(), 'value'))],
            'industry' => ['required', Rule::in(array_column(Industry::cases(), 'value'))],
            'authorized_representative_name' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'established_year' => ['nullable', 'integer', 'min:1900', 'max:'.(int) date('Y')],
            'company_classification' => ['required', Rule::in(array_column(CompanyClassification::cases(), 'value'))],
            'cnic' => ['required', 'string', 'regex:/^\d{5}-\d{7}-\d{1}$/'],
            'cnic_expiry_date' => ['nullable', 'date'],
            'turnover_pkr' => ['nullable', 'integer', 'min:0'],
            'employees_count' => ['nullable', 'integer', 'min:0'],
            'ntn_number' => ['nullable', 'string', 'max:20'],
            'sales_tax_no' => ['nullable', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:1000'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'district' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'cell' => ['required', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'alternate_no' => ['nullable', 'string', 'max:30'],
            'other_chamber_memberships' => ['nullable', 'string', 'max:500'],
            // Optional at submission: the applicant may upload the payment
            // proof here, hand a cheque to the admin at the office, or upload
            // via the portal later when status is Awaiting Payment.
            'payment_proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'payment_date' => ['nullable', 'required_with:payment_proof', 'date_format:Y-m-d', 'before_or_equal:today'],
            'payment_method' => ['nullable', 'required_with:payment_proof', Rule::enum(PaymentMethod::class)],
            'terms_confirmed' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'cnic.regex' => 'CNIC must be in the format: 12345-1234567-1',
            'payment_proof.mimes' => 'Payment proof must be a PDF, JPG, or PNG file.',
            'payment_proof.max' => 'Payment proof must be smaller than 5MB.',
            'terms_confirmed.accepted' => 'You must acknowledge the renewal terms.',
        ];
    }
}
