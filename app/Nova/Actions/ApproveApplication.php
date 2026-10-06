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
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Http\Requests\NovaRequest;

class ApproveApplication extends Action
{
    use InteractsWithQueue;
    use Queueable;

    public $name = 'Approve application';

    public $confirmButtonText = 'Approve';

    public $confirmText = 'This approves the membership, sets the expiry date, generates (or links) a member record, and emails the applicant. The membership ID generated at application creation is used as-is.';

    public function handle(ActionFields $fields, Collection $models): mixed
    {
        $service = app(ApplicationWorkflowService::class);

        $activeUntil = CarbonImmutable::parse((string) $fields->active_until);

        $models->each(fn (Application $application) => $service->approve($application, $activeUntil));

        return Action::message('Application approved. The applicant has been emailed.');
    }

    public function fields(NovaRequest $request): array
    {
        return [
            Date::make('Membership expires on', 'active_until')
                ->rules('required', 'date', 'after:today')
                ->default(now()->addYear()->toDateString())
                ->help('WCCIK memberships run annually (April – March). Pick the date the new/renewed membership runs through.'),
        ];
    }
}
