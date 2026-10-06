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

class RejectApplication extends Action
{
    use InteractsWithQueue;
    use Queueable;

    public $name = 'Reject application';

    public $confirmButtonText = 'Reject';

    public $confirmText = 'This rejects the application and emails the applicant with the reason you provide.';

    public function handle(ActionFields $fields, Collection $models): mixed
    {
        $service = app(ApplicationWorkflowService::class);
        $reason = (string) $fields->rejection_reason;

        $models->each(fn (Application $application) => $service->reject($application, $reason));

        return Action::message('Application rejected. The applicant has been emailed.');
    }

    public function fields(NovaRequest $request): array
    {
        return [
            Textarea::make('Reason', 'rejection_reason')
                ->rules('required', 'string', 'max:1000')
                ->help('This text is sent to the applicant in the rejection email.'),
        ];
    }
}
