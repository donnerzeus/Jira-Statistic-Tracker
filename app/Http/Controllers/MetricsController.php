<?php

namespace App\Http\Controllers;

use App\Services\Analytics\TeamHeatmapService;
use App\Services\Analytics\TrendForecastService;
use App\Services\Jira\DemandMetricsService;
use App\Services\Jira\ServiceMetricsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetricsController extends Controller
{
    public function trend(TrendForecastService $service): JsonResponse
    {
        return response()->json($service->getForecastData());
    }

    public function heatmap(TeamHeatmapService $service): JsonResponse
    {
        return response()->json($service->getHeatmapData());
    }

    public function service(ServiceMetricsService $service): JsonResponse
    {
        // For simple daily/summary view
        // This is a placeholder for now, can be expanded.
        return response()->json([
            'weekly' => $service->getWeeklyServiceMetrics()
        ]);
    }

    public function demand(DemandMetricsService $service): JsonResponse
    {
        return response()->json([
            'weekly' => $service->getWeeklyDemandMetrics()
        ]);
    }
    public function stats(Request $request, ServiceMetricsService $serviceMetrics, DemandMetricsService $demandMetrics): JsonResponse
    {
        $days = $request->input('days', 30); // Default 30 days
        
        return response()->json([
            'service' => $serviceMetrics->getTodayStats(),
            'demand' => $demandMetrics->getLast30DaysStats(),
            'date_range' => [
                'days' => $days,
                'start_date' => Carbon::now()->subDays($days)->format('Y-m-d'),
                'end_date' => Carbon::now()->format('Y-m-d')
            ]
        ]);
    }

    public function charts(Request $request, ServiceMetricsService $serviceMetrics, DemandMetricsService $demandMetrics): JsonResponse
    {
        $days = $request->input('days', 30); // Default 30 days
        
        // Combine Service and Demand Issue Types
        $serviceTypes = $serviceMetrics->getIssueTypeBreakdown(); // returns ['labels' => [], 'data' => []]
        $demandTypes = $demandMetrics->getIssueTypeBreakdown(); // returns ['Type' => Count]

        // Convert Service Types to associative array
        $combinedTypes = [];
        foreach ($serviceTypes['labels'] as $index => $label) {
            $combinedTypes[$label] = ($combinedTypes[$label] ?? 0) + $serviceTypes['data'][$index];
        }

        // Add Demand Types
        foreach ($demandTypes as $label => $count) {
            $combinedTypes[$label] = ($combinedTypes[$label] ?? 0) + $count;
        }

        return response()->json([
            'service_volume' => $serviceMetrics->getTicketVolume($days),
            'service_types' => [
                'labels' => array_keys($combinedTypes),
                'data' => array_values($combinedTypes)
            ],
            'demand_throughput' => $demandMetrics->getThroughput(12),
            'demand_cycle_time' => $demandMetrics->getCycleTimeDistribution(90),
            'board_metrics' => $demandMetrics->getBoardMetrics(331),
        ]);
    }

    public function executive(ServiceMetricsService $serviceMetrics, DemandMetricsService $demandMetrics): JsonResponse
    {
        // Get current stats
        $serviceToday = $serviceMetrics->getTodayStats();
        $demandStats = $demandMetrics->getLast30DaysStats();
        
        // Calculate KPIs
        $totalActiveTickets = $serviceToday['total_tickets'] + $demandStats['total'];
        $slaBreaches = $serviceToday['sla_breaches'];
        $slaComplianceRate = $totalActiveTickets > 0 
            ? round((($totalActiveTickets - $slaBreaches) / $totalActiveTickets) * 100, 1)
            : 100;
        
        // Team Performance Score (simplified - based on SLA compliance and avg lead time)
        $leadTimeScore = $demandStats['avg_lead_time'] > 0 
            ? max(0, 100 - ($demandStats['avg_lead_time'] * 2)) // Penalty for high lead time
            : 100;
        $teamScore = round(($slaComplianceRate + $leadTimeScore) / 2, 1);
        
        // Trend calculation (mock - in production, compare with last period)
        $trend = 'stable'; // 'up', 'down', 'stable'
        $trendPercentage = 0;
        
        // Critical Alerts
        $criticalAlerts = [];
        
        if ($slaBreaches > 0) {
            $criticalAlerts[] = [
                'type' => 'sla_breach',
                'severity' => 'high',
                'message' => "{$slaBreaches} ticket(s) have SLA breaches",
                'count' => $slaBreaches
            ];
        }
        
        if ($demandStats['avg_lead_time'] > 14) {
            $criticalAlerts[] = [
                'type' => 'high_lead_time',
                'severity' => 'medium',
                'message' => "Average lead time is {$demandStats['avg_lead_time']} days (target: <14 days)",
                'value' => $demandStats['avg_lead_time']
            ];
        }
        
        if ($totalActiveTickets > 100) {
            $criticalAlerts[] = [
                'type' => 'high_volume',
                'severity' => 'medium',
                'message' => "High ticket volume: {$totalActiveTickets} active tickets",
                'count' => $totalActiveTickets
            ];
        }

        return response()->json([
            'summary' => [
                'total_active_tickets' => $totalActiveTickets,
                'sla_compliance_rate' => $slaComplianceRate,
                'team_performance_score' => $teamScore,
                'trend' => $trend,
                'trend_percentage' => $trendPercentage
            ],
            'kpis' => [
                'service_today' => [
                    'value' => $serviceToday['total_tickets'],
                    'status' => $serviceToday['total_tickets'] < 10 ? 'good' : ($serviceToday['total_tickets'] < 20 ? 'warning' : 'critical'),
                    'trend' => 'stable'
                ],
                'sla_compliance' => [
                    'value' => $slaComplianceRate,
                    'status' => $slaComplianceRate >= 95 ? 'good' : ($slaComplianceRate >= 93 ? 'warning' : 'critical'),
                    'trend' => 'stable'
                ],
                'avg_lead_time' => [
                    'value' => $demandStats['avg_lead_time'],
                    'status' => $demandStats['avg_lead_time'] <= 7 ? 'good' : ($demandStats['avg_lead_time'] <= 14 ? 'warning' : 'critical'),
                    'trend' => 'stable'
                ],
                'team_score' => [
                    'value' => $teamScore,
                    'status' => $teamScore >= 80 ? 'good' : ($teamScore >= 60 ? 'warning' : 'critical'),
                    'trend' => 'up'
                ]
            ],
            'critical_alerts' => $criticalAlerts
        ]);
    }

    public function advanced(Request $request): JsonResponse
    {
        $advancedService = app(\App\Services\Analytics\AdvancedAnalyticsService::class);
        $days = $request->input('days', 30);

        return response()->json([
            'worklog' => $advancedService->getWorklogAnalytics($days),
            'components_labels' => $advancedService->getComponentLabelAnalytics(),
            'priority_distribution' => $advancedService->getPriorityDistribution(),
            'reopened_issues' => $advancedService->getReopenedIssues($days),
            'resolution_time_by_type' => $advancedService->getResolutionTimeByType(90),
            'comment_activity' => $advancedService->getCommentActivity($days),
            'sprint_velocity' => $advancedService->getSprintVelocity(331),
            'dependencies' => $advancedService->getDependencyAnalysis()
        ]);
    }

    public function predictions(): JsonResponse
    {
        $predictive = app(\App\Services\Analytics\PredictiveAnalyticsService::class);

        return response()->json([
            'volume_prediction' => $predictive->predictNextMonthVolume(),
            'sla_risk' => $predictive->predictSLABreachRisk(),
            'capacity' => $predictive->predictCapacityIssues(),
            'at_risk_projects' => $predictive->identifyAtRiskProjects(),
            'recommendations' => $predictive->generateRecommendations()
        ]);
    }

    public function leaderboard(Request $request): JsonResponse
    {
        $leaderboard = app(\App\Services\Analytics\TeamLeaderboardService::class);
        $days = $request->input('days', 30);

        return response()->json([
            'leaderboard' => $leaderboard->getLeaderboard($days),
            'achievements' => $leaderboard->getTeamAchievements()
        ]);
    }
    
    public function refresh(): JsonResponse
    {
        \Illuminate\Support\Facades\Cache::flush();
        return response()->json(['status' => 'success']);
    }
}
