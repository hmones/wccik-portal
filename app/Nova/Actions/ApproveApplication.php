<?php

namespace App\Nova\Actions;

use App\Models\Application;
use App\Services\Application\ApplicationWorkflowService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class ApproveApplication extends Action
{
    use InteractsWithQueue;
    use Queueable;

    public $name = 'Accept form and request payment';

    public $sole = true;

    public $confirmButtonText = 'Accept form';

    public $confirmText = 'Accept eligibility after receiving the signed form and supporting documents. The applicant will receive your payment instructions and can submit payment from the dashboard. Membership is created only after payment processing.';

    public function handle(ActionFields $fields, Collection $models): mixed
    {
        $models->each(fn (Application $application) => app(ApplicationWorkflowService::class)
            ->acceptForm($application, (string) $fields->payment_instructions));

        return Action::message('Form accepted. Payment instructions have been queued for email. Membership is pending payment processing.');
    }

    public function fields(NovaRequest $request): array
    {
        return [
            Textarea::make('Payment instructions', 'payment_instructions')
                ->rules('required', 'string', 'max:5000')
                ->rows(8)
                ->help('Enter the approved fee, bank name, account title, account number/IBAN, and cheque/pay-order instructions. These are shown to the applicant and emailed.'),
        ];
    }
}
