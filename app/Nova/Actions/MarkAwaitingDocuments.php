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

class MarkAwaitingDocuments extends Action
{
    use InteractsWithQueue;
    use Queueable;

    public $name = 'Mark Awaiting Documents';

    public $confirmButtonText = 'Request documents';

    public $confirmText = 'This will email the applicant to request their physical documents.';

    public function handle(ActionFields $fields, Collection $models): mixed
    {
        $service = app(ApplicationWorkflowService::class);
        $models->each(fn (Application $application) => $service->markAwaitingDocuments($application));

        return Action::message('Applicants have been emailed.');
    }

    public function fields(NovaRequest $request): array
    {
        return [];
    }
}
