<?php

namespace App\Nova\Dashboards;

use App\Nova\Metrics\ApplicationsByStatus;
use App\Nova\Metrics\TotalApplications;
use Laravel\Nova\Card;
use Laravel\Nova\Dashboards\Main as Dashboard;

class Main extends Dashboard
{
    /**
     * @return array<int, Card>
     */
    public function cards(): array
    {
        return [
            new TotalApplications,
            new ApplicationsByStatus,
        ];
    }
}
