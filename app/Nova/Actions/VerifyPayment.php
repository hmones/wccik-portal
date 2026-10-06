<?php

namespace App\Nova\Actions;

use App\Enums\PaymentMethod;
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
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class VerifyPayment extends Action
{
    use InteractsWithQueue;
    use Queueable;

    public $name = 'Verify payment';

    public $sole = true;

    public $confirmButtonText = 'Save decision';

    public $confirmText = 'Review the uploaded payment proof above. If verified, the application moves to Ready for Approval. If rejected, it goes back to Awaiting Payment and the applicant is emailed.';

    public function handle(ActionFields $fields, Collection $models): mixed
    {
        $service = app(ApplicationWorkflowService::class);

        $verified = (bool) $fields->verified;
        $date = $fields->payment_date ? CarbonImmutable::parse((string) $fields->payment_date) : null;
        $method = $fields->payment_method ? (string) $fields->payment_method : null;
        $notes = $fields->notes ? (string) $fields->notes : null;

        $models->each(fn (Application $application) => $service->recordPaymentDecision(
            $application,
            $verified,
            $date,
            $method,
            $notes,
        ));

        return Action::message($verified
            ? 'Payment verified. Application moved to Ready for Approval.'
            : 'Payment rejected. Applicant has been emailed.');
    }

    public function fields(NovaRequest $request): array
    {
        return [
            Boolean::make('Verified')
                ->trueValue(true)
                ->falseValue(false)
                ->default(true)
                ->help('Open the Payment proof file above in a new tab to review it before deciding.'),

            Date::make('Payment date', 'payment_date')
                ->default(now()->toDateString())
                ->help('When the payment was received.'),

            Select::make('Payment method', 'payment_method')
                ->options(collect(PaymentMethod::cases())
                    ->mapWithKeys(fn ($m) => [$m->value => $m->label()])
                    ->all())
                ->displayUsingLabels()
                ->nullable(),

            Textarea::make('Notes')
                ->nullable()
                ->rules('nullable', 'string', 'max:1000')
                ->help('Internal notes (not emailed on verify). If rejecting, the applicant gets the standard awaiting-payment template.'),
        ];
    }
}
