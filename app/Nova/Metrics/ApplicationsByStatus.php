<?php

namespace App\Nova\Metrics;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use DateTimeInterface;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

class ApplicationsByStatus extends Partition
{
    public $name = 'Applications by Status';

    public function calculate(NovaRequest $request): PartitionResult
    {
        return $this->count($request, Application::class, groupBy: 'status')
            ->label(fn ($value) => ApplicationStatus::from($value)->label())
            ->colors([
                ApplicationStatus::Submitted->value => '#3B82F6',
                ApplicationStatus::AwaitingDocuments->value => '#F59E0B',
                ApplicationStatus::AwaitingPayment->value => '#F97316',
                ApplicationStatus::ReadyForApproval->value => '#8B5CF6',
                ApplicationStatus::Approved->value => '#10B981',
                ApplicationStatus::Rejected->value => '#EF4444',
            ]);
    }

    public function cacheFor(): ?DateTimeInterface
    {
        return null;
    }

    public function uriKey(): string
    {
        return 'applications-by-status';
    }
}
