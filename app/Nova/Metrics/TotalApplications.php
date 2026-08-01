<?php

namespace App\Nova\Metrics;

use App\Models\Application;
use DateTimeInterface;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Value;
use Laravel\Nova\Metrics\ValueResult;
use Laravel\Nova\Nova;

class TotalApplications extends Value
{
    public $name = 'Total Applications';

    public function calculate(NovaRequest $request): ValueResult
    {
        return $this->count($request, Application::class);
    }

    public function ranges(): array
    {
        return [
            'TODAY' => Nova::__('Today'),
            'MTD' => Nova::__('Month To Date'),
            'YTD' => Nova::__('Year To Date'),
            30 => Nova::__('30 Days'),
            365 => Nova::__('365 Days'),
            'ALL' => Nova::__('All Time'),
        ];
    }

    public function cacheFor(): ?DateTimeInterface
    {
        return null;
    }

    public function uriKey(): string
    {
        return 'total-applications';
    }
}
