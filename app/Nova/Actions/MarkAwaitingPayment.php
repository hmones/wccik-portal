<?php

namespace App\Nova\Actions;

use App\Models\Application;
use App\Services\Application\ApplicationWorkflowService;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Http\Requests\NovaRequest;

class MarkAwaitingPayment extends Action
{
    use InteractsWithQueue;
    use Queueable;

    public $name = 'Resend payment instructions';

    public $confirmButtonText = 'Resend payment instructions';

    public $confirmText = 'Resend the payment instructions for an already accepted form. This cannot accept a form or activate membership.';

    public function handle(ActionFields $fields, Collection $models): mixed
    {
        $service = app(ApplicationWorkflowService::class);
        $models->each(fn (Application $application) => $service->markAwaitingPayment($application));

        return Action::message('Applicants have been emailed.');
    }

    public function fields(NovaRequest $request): array
    {
        return [];
    }
}
