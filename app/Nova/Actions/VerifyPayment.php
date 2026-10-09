<?php

namespace App\Nova\Actions;

use App\Models\Application;
use App\Services\Application\ApplicationWorkflowService;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class VerifyPayment extends Action
{
    use InteractsWithQueue;
    use Queueable;

    public $name = 'Process payment and create membership';

    public $sole = true;

    public $confirmButtonText = 'Process payment';

    public $confirmText = 'Review the accepted form, receipt, payment date and method. Confirm the actual processing date and membership expiry. Verified payment creates or renews membership and queues the approval and seven-day collection emails. Rejected payment requests a correction.';

    public function handle(ActionFields $fields, Collection $models): mixed
    {
        $service = app(ApplicationWorkflowService::class);

        $verified = (bool) $fields->verified;
        $notes = $fields->notes ? (string) $fields->notes : null;

        $models->each(fn (Application $application) => $service->recordPaymentDecision(
            $application,
            $verified,
            notes: $notes,
            activeUntil: $verified && $fields->active_until ? CarbonImmutable::parse((string) $fields->active_until) : null,
            processedAt: $verified && $fields->processed_at ? CarbonImmutable::parse((string) $fields->processed_at) : null,
        ));

        return Action::message($verified
            ? ($models->every(fn (Application $application) => $application->isApproved())
                ? 'Payment processed. Membership activated; approval and collection emails queued.'
                : 'Payment decision recorded.')
            : 'Payment rejected. Correction email queued for the applicant.');
    }

    public function fields(NovaRequest $request): array
    {
        return [
            Boolean::make('Verified')
                ->trueValue(true)
                ->falseValue(false)
                ->default(true)
                ->help('Open the Payment proof file above in a new tab to review it before deciding.'),

            Date::make('Payment processed on', 'processed_at')
                ->default(today()->toDateString())
                ->rules('nullable', 'required_if:verified,1,true', 'date_format:Y-m-d', 'before_or_equal:today')
                ->help('Actual cheque/pay-order realization or transfer processing date. Backdated dates are supported.'),

            Date::make('Membership expires on', 'active_until')
                ->default(CarbonImmutable::create(today()->month >= 4 ? today()->year + 1 : today()->year, 3, 31)->toDateString())
                ->rules('nullable', 'required_if:verified,1,true', 'date', 'after:today'),

            Textarea::make('Notes')
                ->nullable()
                ->rules('nullable', 'string', 'max:1000')
                ->help('Internal notes when verifying. If rejecting payment, these notes explain the required correction in the applicant email.'),
        ];
    }
}
