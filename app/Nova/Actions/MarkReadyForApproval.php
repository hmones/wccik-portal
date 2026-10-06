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

class MarkReadyForApproval extends Action
{
    use InteractsWithQueue;
    use Queueable;

    public $name = 'Mark Ready for Approval';

    public $confirmButtonText = 'Mark ready';

    public $confirmText = 'Internal transition. The applicant is NOT notified.';

    public function handle(ActionFields $fields, Collection $models): mixed
    {
        $service = app(ApplicationWorkflowService::class);
        $models->each(fn (Application $application) => $service->markReadyForApproval($application));

        return Action::message('Applications moved to ready for approval.');
    }

    public function fields(NovaRequest $request): array
    {
        return [];
    }
}
