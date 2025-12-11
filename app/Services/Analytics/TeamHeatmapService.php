<?php

namespace App\Services\Analytics;

use App\Services\Jira\ServiceMetricsService;
use App\Services\Jira\DemandMetricsService;

class TeamHeatmapService
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

    public function getHeatmapData(): array
    {
        $serviceData = collect($this->serviceMetrics->getTeamServiceMetrics());
        $demandData = collect($this->demandMetrics->getTeamDemandMetrics());

        // Merge by assignee
        $allAssignees = $serviceData->pluck('assignee')
            ->merge($demandData->pluck('assignee'))
            ->unique()
            ->values();

        $heatmap = $allAssignees->map(function ($assignee) use ($serviceData, $demandData) {
            $service = $serviceData->firstWhere('assignee', $assignee) ?? [];
            $demand = $demandData->firstWhere('assignee', $assignee) ?? [];

            return [
                'assignee' => $assignee,
                'department' => $this->getDepartmentForUser($assignee),
                'service_ticket_count' => $service['service_ticket_count'] ?? 0,
                'demand_ticket_count' => $demand['demand_ticket_count'] ?? 0,
                'avg_response_minutes' => 0, // Placeholder
                'avg_resolution_hours' => $service['avg_resolution_hours'] ?? 0,
                'avg_lead_time_days' => $demand['avg_lead_time_days'] ?? 0,
                'avg_cycle_time_days' => $demand['avg_cycle_time_days'] ?? 0,
                'sla_breach_rate_percent' => $service['sla_breach_rate_percent'] ?? 0,
            ];
        });

        return $heatmap->toArray();
    }

    protected function getDepartmentForUser(string $name): string
    {
        // Mock mapping. In real usage, this should come from a config file or DB.
        $map = [
            'baris' => 'IT Operations',
            'elif' => 'HR Systems',
            'ahmet' => 'Software Dev',
            'mehmet' => 'Network',
        ];
        
        foreach ($map as $key => $dept) {
            if (stripos($name, $key) !== false) return $dept;
        }
        
        return 'General';
    }
}
