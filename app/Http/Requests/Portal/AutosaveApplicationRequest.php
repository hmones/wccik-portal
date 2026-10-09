<?php

namespace App\Http\Requests\Portal;

use App\Enums\CompanyClassification;
use App\Enums\Industry;
use App\Enums\MembershipClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AutosaveApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Autosave is intentionally lenient, every field is optional so the user
     * can type piecemeal. Final validation lives on SubmitApplicationRequest.
     */
    public function rules(): array
    {
        return [
            'membership_class' => ['nullable', Rule::in(array_column(MembershipClass::cases(), 'value'))],
            'industry' => ['nullable', Rule::in(array_column(Industry::cases(), 'value'))],
            'authorized_representative_name' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'established_year' => ['nullable', 'integer', 'min:1900', 'max:'.(int) date('Y')],
            'company_classification' => ['nullable', Rule::in(array_column(CompanyClassification::cases(), 'value'))],
            'cnic' => ['nullable', 'string', 'max:20'],
            'cnic_expiry_date' => ['nullable', 'date'],
            'turnover_pkr' => ['nullable', 'integer', 'min:0'],
            'employees_count' => ['nullable', 'integer', 'min:0'],
            'has_ntn' => ['nullable', 'boolean'],
            'ntn_number' => ['nullable', 'string', 'max:20'],
            'sales_tax_no' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'district' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'cell' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'alternate_no' => ['nullable', 'string', 'max:30'],
            'other_chamber_memberships' => ['nullable', 'string', 'max:500'],
            'terms_confirmed' => ['nullable', 'boolean'],
        ];
    }
}
