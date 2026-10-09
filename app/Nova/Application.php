<?php

namespace App\Nova;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\CompanyClassification;
use App\Enums\Industry;
use App\Enums\MembershipClass;
use App\Enums\PaymentMethod;
use App\Nova\Actions\ApproveApplication;
use App\Nova\Actions\MarkAwaitingPayment;
use App\Nova\Actions\RejectApplication;
use App\Nova\Actions\VerifyPayment;
use App\Services\Application\ApplicationWorkflowService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;
use Laravel\Nova\Resource;

class Application extends Resource
{
    public static string $model = \App\Models\Application::class;

    public static $title = 'authorized_representative_name';

    public static $search = [
        'authorized_representative_name',
        'email',
        'cnic',
        'company_name',
        'ntn_number',
        'membership_id',
        'existing_membership_number',
    ];

    public static function label(): string
    {
        return 'Applications';
    }

    public static function singularLabel(): string
    {
        return 'Application';
    }

    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            // ── Status & Decision ───────────────────────────────────────
            Panel::make('Status & decision', [
                Badge::make('Status')
                    ->map([
                        ApplicationStatus::Draft->value => 'info',
                        ApplicationStatus::Submitted->value => 'info',
                        ApplicationStatus::AwaitingDocuments->value => 'warning',
                        ApplicationStatus::AwaitingPayment->value => 'warning',
                        ApplicationStatus::ReadyForApproval->value => 'info',
                        ApplicationStatus::Approved->value => 'success',
                        ApplicationStatus::Rejected->value => 'danger',
                    ])
                    ->labels(collect(ApplicationStatus::cases())
                        ->mapWithKeys(fn ($status) => [$status->value => $status->label()])
                        ->all()),

                Select::make('Type')
                    ->options(collect(ApplicationType::cases())
                        ->mapWithKeys(fn ($type) => [$type->value => $type->label()])
                        ->all())
                    ->displayUsingLabels()
                    ->sortable()
                    ->filterable()
                    ->readonly(),

                Select::make('Status')
                    ->options(collect(ApplicationStatus::cases())
                        ->mapWithKeys(fn ($status) => [$status->value => $status->label()])
                        ->all())
                    ->displayUsingLabels()
                    ->sortable()
                    ->filterable()
                    ->onlyOnForms()
                    ->readonly()
                    ->help('Accept the form first, then process payment to create membership.'),

                Text::make('Membership ID')
                    ->sortable()
                    ->readonly()
                    ->help('Issued when accepted information and processed payment activate a new membership. Not editable.'),

                Date::make('Submitted At')
                    ->sortable()
                    ->nullable()
                    ->readonly(),

                Textarea::make('Rejection Reason')
                    ->readonly()
                    ->nullable()
                    ->rows(3)
                    ->help('Shown to the applicant in the rejection email if the Reject action is used; also kept here for records.'),

                Date::make('Form accepted at', 'admin_approved_at')
                    ->readonly()
                    ->nullable()
                    ->help('Acceptance opens the payment stage and sends the applicant payment instructions.')
                    ->onlyOnDetail(),

                Date::make('Approved membership expiry', 'admin_approved_until')
                    ->readonly()
                    ->nullable()
                    ->help('Set when payment is processed and membership is created or renewed.')
                    ->onlyOnDetail(),

                Text::make('Form accepted', fn () => $this->resource->admin_approved_at !== null ? 'Yes' : 'Pending')
                    ->exceptOnForms(),

                Text::make('Correction policy', fn () => 'Changing applicant or company information resets both approvals and office receipt checks. Obtain the corrected signed form and review again.')
                    ->onlyOnForms(),

                Text::make('Filled application form', 'id')
                    ->onlyOnDetail()
                    ->displayUsing(fn ($id) => sprintf(
                        '<a href="%s" target="_blank" rel="noopener" class="link-default inline-flex items-center gap-1 font-semibold">'
                        .'Open filled form in a new tab <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17l10-10M7 7h10v10"/></svg>'
                        .'</a>',
                        route('admin.applications.pdf', ['application' => $id]),
                    ))
                    ->asHtml(),
            ]),

            // ── Documents received (checklist) ──────────────────────────
            Panel::make('Documents received at the office', [
                Boolean::make('Physical form received', 'physical_form_received')
                    ->help('The signed, printed application form.'),
                Boolean::make('Supporting documents received', 'documents_received')
                    ->help('CNIC copy, photographs, tax return, NTN, specimen signature, etc.'),
            ]),

            // ── Payment ─────────────────────────────────────────────────
            Panel::make('Payment', [
                Textarea::make('Payment instructions')->readonly()->nullable()->rows(5),
                Date::make('Payment submitted at', 'payment_submitted_at')->readonly()->nullable(),
                Date::make('Payment processed on', 'payment_processed_at')->readonly()->nullable(),
                File::make('Payment proof', 'payment_proof_path')
                    ->readonly(fn () => ! $this->resource->canSubmitPayment())
                    ->disk('public')
                    ->nullable()
                    ->download(fn ($request, $model) => $model->payment_proof_path
                        ? Storage::disk('public')->download($model->payment_proof_path)
                        : null,
                    )
                    ->acceptedTypes('.pdf,.jpg,.jpeg,.png')
                    ->rules('nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120')
                    ->help('Uploaded by the applicant or scanned by the office. Replacing or removing it resets payment verification.'),

                Boolean::make('Payment verified')->readonly()->help('Use Process payment and create membership after reviewing the receipt, date and method.'),

                Date::make('Payment date')->nullable()->readonly(fn () => ! $this->resource->canSubmitPayment())
                    ->rules('nullable', Rule::requiredIf(fn () => filled($this->resource->payment_proof_path) || $request->hasFile('payment_proof_path')), 'date_format:Y-m-d', 'before_or_equal:today')
                    ->help('Date the applicant made the payment. Correcting it requires payment verification again.'),

                Select::make('Payment method')
                    ->readonly(fn () => ! $this->resource->canSubmitPayment())
                    ->options(collect(PaymentMethod::cases())
                        ->mapWithKeys(fn ($payment) => [$payment->value => $payment->label()])
                        ->all())
                    ->displayUsingLabels()
                    ->nullable()
                    ->rules('nullable', Rule::requiredIf(fn () => filled($this->resource->payment_proof_path) || $request->hasFile('payment_proof_path')), Rule::enum(PaymentMethod::class))
                    ->help('Supplied by the applicant or recorded from the office receipt. Correcting it requires payment verification again.')
                    ->filterable(),

                Textarea::make('Payment notes')->readonly()->nullable()->rows(3),
            ]),

            // ── Applicant ────────────────────────────────────────────────
            Panel::make('Applicant', [
                Text::make('Authorized Representative Name')
                    ->sortable()
                    ->rules('required', 'max:255'),

                Text::make('Email')
                    ->sortable()
                    ->rules('required', 'email', 'max:255'),

                Text::make('CNIC', 'cnic')
                    ->rules('required', 'regex:/^\d{5}-\d{7}-\d{1}$/')
                    ->help('Authorized representative CNIC.'),

                Date::make('CNIC expiry date', 'cnic_expiry_date')->nullable(),

                Text::make('Cell')
                    ->rules('required', 'max:20'),

                Text::make('WhatsApp')
                    ->nullable()
                    ->rules('nullable', 'max:30'),

                Text::make('Phone')->nullable(),
                Text::make('Alternate number', 'alternate_no')->nullable(),
            ]),

            // ── Company ──────────────────────────────────────────────────
            Panel::make('Company', [
                Text::make('Company Name')->rules('required', 'max:255')->sortable()
                    ->help('Corrections reset information and payment approvals and require a corrected signed form and document checks.'),

                Select::make('Membership class', 'membership_class')
                    ->options(collect(MembershipClass::cases())->mapWithKeys(fn ($class) => [$class->value => $class->label()])->all())
                    ->displayUsingLabels()->rules('required'),

                Select::make('Industry')
                    ->options(collect(Industry::cases())->mapWithKeys(fn ($industry) => [$industry->value => $industry->label()])->all())
                    ->displayUsingLabels()->rules('required'),

                Text::make('Website')->nullable()->rules('nullable', 'url', 'max:255'),
                Number::make('Established year', 'established_year')->nullable()->rules('nullable', 'integer', 'min:1900', 'max:'.now()->year),
                Number::make('Turnover PKR', 'turnover_pkr')->nullable()->rules('nullable', 'integer', 'min:0'),
                Number::make('Employees count', 'employees_count')->nullable()->rules('nullable', 'integer', 'min:0'),

                Select::make('Company Classification')
                    ->options(collect(CompanyClassification::cases())
                        ->mapWithKeys(fn ($c) => [$c->value => $c->label()])
                        ->all())
                    ->displayUsingLabels()
                    ->nullable()
                    ->filterable(),

                Textarea::make('Address')->rules('required', 'max:1000')->rows(3),

                Text::make('Postal code', 'postal_code')->nullable(),

                Text::make('District')->nullable()->sortable()->filterable(),
                Textarea::make('Other chamber memberships', 'other_chamber_memberships')->nullable(),
            ]),

            // ── NTN (new member) ─────────────────────────────────────────
            Panel::make('NTN', [
                Boolean::make('Has NTN', 'has_ntn')->nullable(),

                Text::make('NTN Number')
                    ->nullable()
                    ->help('Required if Has NTN is Yes.'),

                Text::make('Sales tax number', 'sales_tax_no')->nullable()->rules('nullable', 'max:30'),

                Text::make('NTN Reason')
                    ->nullable()
                    ->help('Reason for not having NTN.'),
            ]),

            // ── Renewal link ─────────────────────────────────────────────
            Panel::make('Renewal link', [
                Text::make('Existing Membership Number')
                    ->nullable()
                    ->sortable()
                    ->readonly()
                    ->help('Filled for renewal applications only.'),

                Text::make('Status Token')
                    ->onlyOnDetail()
                    ->readonly(),
            ]),
        ];
    }

    public static function indexQuery(NovaRequest $request, $query): Builder
    {
        return $query->latest('submitted_at');
    }

    public function actions(NovaRequest $request): array
    {
        return [
            (new ApproveApplication)->canRun(fn ($request, $application) => $application->canBeReviewed()
                && $application->physical_form_received && $application->documents_received),
            (new VerifyPayment)->canRun(fn ($request, $application) => $application->canSubmitPayment()
                && filled($application->payment_proof_path)),
            (new MarkAwaitingPayment)->canRun(fn ($request, $application) => $application->canSubmitPayment()
                && ! $application->payment_verified),
            (new RejectApplication)->canRun(fn ($request, $application) => $application->canBeReviewed()),
        ];
    }

    public static function afterUpdate(NovaRequest $request, Model $model): void
    {
        if ($model instanceof \App\Models\Application && $model->canSubmitPayment()
            && $model->wasChanged(['payment_proof_path', 'payment_date', 'payment_method'])
            && filled($model->payment_proof_path) && $model->payment_date !== null && $model->payment_method !== null) {
            app(ApplicationWorkflowService::class)->recordPaymentSubmission(
                $model, $model->payment_proof_path, $model->payment_date->toDateString(), $model->payment_method->value,
            );
        }
    }

    public static function authorizedToCreate(Request $request): bool
    {
        return false;
    }

    public function authorizedToUpdate(Request $request): bool
    {
        return ! $this->resource->status->isTerminal() && parent::authorizedToUpdate($request);
    }
}
