<?php

namespace App\Nova;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\CompanyClassification;
use App\Enums\PaymentMethod;
use App\Nova\Actions\ApproveApplication;
use App\Nova\Actions\MarkAwaitingDocuments;
use App\Nova\Actions\MarkAwaitingPayment;
use App\Nova\Actions\MarkReadyForApproval;
use App\Nova\Actions\RejectApplication;
use App\Nova\Actions\VerifyPayment;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\File;
use Laravel\Nova\Fields\ID;
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
            // Top of the page: what state is this in, what did we agree, and
            // the raw status override. The dropdown of workflow actions is
            // just to the right of the page title, one click away.
            Panel::make('Status & decision', [
                Badge::make('Status')
                    ->map([
                        ApplicationStatus::Draft->value => 'info',
                        ApplicationStatus::Submitted->value => 'info',
                        ApplicationStatus::AwaitingDocuments->value => 'warning',
                        ApplicationStatus::AwaitingPayment->value => 'warning',
                        ApplicationStatus::ReadyForApproval->value => 'success',
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
                    ->filterable(),

                Select::make('Status')
                    ->options(collect(ApplicationStatus::cases())
                        ->mapWithKeys(fn ($status) => [$status->value => $status->label()])
                        ->all())
                    ->displayUsingLabels()
                    ->sortable()
                    ->filterable()
                    ->onlyOnForms(),

                Text::make('Membership ID')
                    ->sortable()
                    ->readonly()
                    ->help('Automatically assigned when the application is created. Not editable.'),

                Date::make('Submitted At')
                    ->sortable()
                    ->nullable()
                    ->readonly(),

                Textarea::make('Rejection Reason')
                    ->nullable()
                    ->rows(3)
                    ->help('Shown to the applicant in the rejection email if the Reject action is used; also kept here for records.'),

                Date::make('Admin pre-approved at', 'admin_approved_at')
                    ->readonly()
                    ->nullable()
                    ->help('Set when an admin ran Approve but payment was not yet verified. When payment is later verified the application auto-finalises using the expiry date below.')
                    ->onlyOnDetail(),

                Date::make('Pending approval until', 'admin_approved_until')
                    ->readonly()
                    ->nullable()
                    ->help('The expiry date the admin chose at pre-approval. Applied to the member record on final approval.')
                    ->onlyOnDetail(),

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
                File::make('Payment proof', 'payment_proof_path')
                    ->disk('public')
                    ->nullable()
                    ->prunable()
                    ->download(fn ($request, $model) => $model->payment_proof_path
                        ? Storage::disk('public')->download($model->payment_proof_path)
                        : null,
                    )
                    ->acceptedTypes('.pdf,.jpg,.jpeg,.png')
                    ->help('Uploaded by the applicant with the renewal form, or added here for new members.'),

                Boolean::make('Payment verified'),

                Date::make('Payment date')->nullable(),

                Select::make('Payment method')
                    ->options(collect(PaymentMethod::cases())
                        ->mapWithKeys(fn ($payment) => [$payment->value => $payment->label()])
                        ->all())
                    ->displayUsingLabels()
                    ->nullable()
                    ->filterable(),

                Textarea::make('Payment notes')->nullable()->rows(3),
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
                    ->rules('required', 'max:20')
                    ->help('Authorized representative CNIC.'),

                Text::make('Cell')
                    ->rules('required', 'max:20'),

                Text::make('WhatsApp')
                    ->rules('required', 'max:20'),

                Text::make('Phone')->nullable(),
            ]),

            // ── Company ──────────────────────────────────────────────────
            Panel::make('Company', [
                Text::make('Company Name')->nullable()->sortable(),

                Select::make('Company Classification')
                    ->options(collect(CompanyClassification::cases())
                        ->mapWithKeys(fn ($c) => [$c->value => $c->label()])
                        ->all())
                    ->displayUsingLabels()
                    ->nullable()
                    ->filterable(),

                Textarea::make('Address')->nullable()->rows(3),

                Text::make('District')->nullable()->sortable()->filterable(),
            ]),

            // ── NTN (new member) ─────────────────────────────────────────
            Panel::make('NTN', [
                Boolean::make('Has NTN', 'has_ntn')->nullable(),

                Text::make('NTN Number')
                    ->nullable()
                    ->help('Required if Has NTN is Yes.'),

                Text::make('NTN Reason')
                    ->nullable()
                    ->help('Reason for not having NTN.'),
            ]),

            // ── Renewal link ─────────────────────────────────────────────
            Panel::make('Renewal link', [
                Text::make('Existing Membership Number')
                    ->nullable()
                    ->sortable()
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
        // Order matters — this is also the order they appear in the dropdown
        // on the detail page. Keep the daily-use ones at the top.
        //
        // The filled PDF is not an action — it's a direct download link
        // rendered as a field in the "Filled application form" panel below so
        // admins can right-click → open in new tab.
        return [
            (new ApproveApplication),
            (new RejectApplication),
            (new VerifyPayment),
            (new MarkAwaitingDocuments),
            (new MarkAwaitingPayment),
            (new MarkReadyForApproval),
        ];
    }
}
