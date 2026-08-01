<?php

namespace App\Nova;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Enums\CompanyClassification;
use App\Enums\PaymentMethod;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Date;
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

            // ── Application ─────────────────────────────────────────────
            Panel::make('Application', [
                Badge::make('Status')
                    ->map([
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
                    ->nullable()
                    ->sortable()
                    ->help('Generated upon approval and payment verification.'),

                Text::make('Status Token')
                    ->onlyOnDetail()
                    ->readonly(),

                Date::make('Submitted At')
                    ->sortable()
                    ->nullable()
                    ->readonly(),
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

            // ── Renewal ──────────────────────────────────────────────────
            Panel::make('Renewal', [
                Text::make('Existing Membership Number')
                    ->nullable()
                    ->sortable()
                    ->help('Filled for renewal applications only.'),

                Text::make('Payment Proof', 'payment_proof_path')
                    ->nullable()
                    ->onlyOnDetail()
                    ->readonly(),
            ]),

            // ── Admin Review ─────────────────────────────────────────────
            Panel::make('Admin Review', [
                Boolean::make('Physical Form Received'),
                Boolean::make('Documents Received'),
                Textarea::make('Rejection Reason')->nullable()->rows(3),
            ]),

            // ── Payment ──────────────────────────────────────────────────
            Panel::make('Payment', [
                Select::make('Payment Method')
                    ->options(collect(PaymentMethod::cases())
                        ->mapWithKeys(fn ($payment) => [$payment->value => $payment->label()])
                        ->all())
                    ->displayUsingLabels()
                    ->nullable()
                    ->filterable(),

                Boolean::make('Payment Verified'),

                Date::make('Payment Date')->nullable(),

                Textarea::make('Payment Notes')->nullable()->rows(3),
            ]),
        ];
    }

    public static function indexQuery(NovaRequest $request, $query): Builder
    {
        return $query->latest('submitted_at');
    }
}
