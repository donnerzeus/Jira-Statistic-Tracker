<?php

namespace App\Services\Analytics;

use App\Services\Jira\ServiceMetricsService;
use App\Services\Jira\DemandMetricsService;

class TrendForecastService
{
    protected ServiceMetricsService $serviceMetrics;
    protected DemandMetricsService $demandMetrics;

    public function __construct(
        ServiceMetricsService $serviceMetrics,
        DemandMetricsService $demandMetrics
    ) {
        $this->serviceMetrics = $serviceMetrics;
        $this->demandMetrics = $demandMetrics;
    }

    public function getForecastData(): array
    {
        $serviceWeekly = $this->serviceMetrics->getWeeklyServiceMetrics(12);
        $demandWeekly = $this->demandMetrics->getWeeklyDemandMetrics(12);

        // Merge keys to ensure alignment
        $allKeys = array_unique(array_merge(array_keys($serviceWeekly), array_keys($demandWeekly)));
        sort($allKeys);

        $serviceCounts = [];
        $demandCounts = [];
        $totalCounts = [];

        foreach ($allKeys as $key) {
            $s = $serviceWeekly[$key] ?? 0;
            $d = $demandWeekly[$key] ?? 0;
            $serviceCounts[] = $s;
            $demandCounts[] = $d;
            $totalCounts[] = $s + $d;
        }

        // Forecast: Avg of last 4 weeks * 4.3
        $last4WeeksTotal = array_slice($totalCounts, -4);
        $avgWeekly = count($last4WeeksTotal) > 0 ? array_sum($last4WeeksTotal) / count($last4WeeksTotal) : 0;
        
        $nextMonthForecast = round($avgWeekly * 4.3);

        // Simple breakdown forecast (proportional)
        $last4Service = array_slice($serviceCounts, -4);
        $avgService = count($last4Service) > 0 ? array_sum($last4Service) / count($last4Service) : 0;
        $nextMonthService = round($avgService * 4.3);
        
        $nextMonthDemand = $nextMonthForecast - $nextMonthService;

        return [
            'weekly' => [
                'labels' => $allKeys,
                'service_counts' => $serviceCounts,
                'demand_counts' => $demandCounts,
                'total_counts' => $totalCounts,
            ],
            'forecast' => [
                'next_month_ticket_forecast' => $nextMonthForecast,
                'next_month_service_forecast' => $nextMonthService,
                'next_month_demand_forecast' => $nextMonthDemand,
                // Placeholder for SLA forecast, can be enriched in controller or here if we fetch breach rates
                'sla_breach_forecast' => 0, 
                'current_breach_rate_percent' => 0
            ]
        ];
    }
}
