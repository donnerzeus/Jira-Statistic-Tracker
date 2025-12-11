<?php

namespace App\Services\Analytics;

use App\Services\Jira\DemandMetricsService;
use App\Services\Jira\ServiceMetricsService;
use Carbon\Carbon;

class PredictiveAnalyticsService
{
    protected ServiceMetricsService $serviceMetrics;
    protected DemandMetricsService $demandMetrics;

    public function __construct(ServiceMetricsService $serviceMetrics, DemandMetricsService $demandMetrics)
    {
        $this->serviceMetrics = $serviceMetrics;
        $this->demandMetrics = $demandMetrics;
    }

    /**
     * Predict next month's ticket volume using linear regression
     */
    public function predictNextMonthVolume(): array
    {
        $volumeData = $this->serviceMetrics->getTicketVolume(90);
        
        $createdData = $volumeData['created'] ?? [];
        
        if (count($createdData) < 7) {
            return [
                'predicted_volume' => 0,
                'confidence' => 0,
                'trend' => 'insufficient_data'
            ];
        }

        // Simple linear regression
        $n = count($createdData);
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($createdData as $i => $value) {
            $sumX += $i;
            $sumY += $value;
            $sumXY += $i * $value;
            $sumX2 += $i * $i;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;

        // Predict next 30 days
        $prediction = $intercept + $slope * ($n + 30);
        
        // Calculate confidence based on variance
        $variance = 0;
        foreach ($createdData as $i => $value) {
            $predicted = $intercept + $slope * $i;
            $variance += pow($value - $predicted, 2);
        }
        $stdDev = sqrt($variance / $n);
        $confidence = max(0, min(100, 100 - ($stdDev / max($createdData) * 100)));

        return [
            'predicted_volume' => round($prediction),
            'confidence' => round($confidence, 2),
            'trend' => $slope > 0 ? 'increasing' : ($slope < 0 ? 'decreasing' : 'stable'),
            'slope' => round($slope, 4)
        ];
    }

    /**
     * Predict SLA breach risk
     */
    public function predictSLABreachRisk(): array
    {
        $stats = $this->serviceMetrics->getTodayStats();
        $totalTickets = $stats['total_tickets'];
        $currentBreaches = $stats['sla_breaches'];

        if ($totalTickets == 0) {
            return [
                'risk_level' => 'low',
                'risk_percentage' => 0,
                'predicted_breaches' => 0
            ];
        }

        $breachRate = ($currentBreaches / $totalTickets) * 100;
        
        // Predict based on current rate
        $predictedBreaches = round($totalTickets * 0.1 * ($breachRate / 10));

        $riskLevel = 'low';
        if ($breachRate > 10) {
            $riskLevel = 'critical';
        } elseif ($breachRate > 5) {
            $riskLevel = 'high';
        } elseif ($breachRate > 2) {
            $riskLevel = 'medium';
        }

        return [
            'risk_level' => $riskLevel,
            'risk_percentage' => round($breachRate, 2),
            'predicted_breaches' => $predictedBreaches,
            'recommendation' => $this->getSLARecommendation($riskLevel)
        ];
    }

    /**
     * Predict team capacity issues
     */
    public function predictCapacityIssues(): array
    {
        $demandStats = $this->demandMetrics->getLast30DaysStats();
        $avgLeadTime = $demandStats['avg_lead_time'];

        $capacityStatus = 'healthy';
        $recommendation = 'Team capacity is optimal';

        if ($avgLeadTime > 21) {
            $capacityStatus = 'critical';
            $recommendation = 'Urgent: Consider adding team members or reducing scope';
        } elseif ($avgLeadTime > 14) {
            $capacityStatus = 'warning';
            $recommendation = 'Warning: Team is approaching capacity limits';
        } elseif ($avgLeadTime > 10) {
            $capacityStatus = 'attention';
            $recommendation = 'Monitor closely: Lead time is increasing';
        }

        return [
            'status' => $capacityStatus,
            'avg_lead_time' => $avgLeadTime,
            'recommendation' => $recommendation,
            'predicted_bottleneck_date' => $avgLeadTime > 14 
                ? Carbon::now()->addDays(round($avgLeadTime * 0.5))->format('Y-m-d')
                : null
        ];
    }

    /**
     * Identify at-risk projects
     */
    public function identifyAtRiskProjects(): array
    {
        $demandStats = $this->demandMetrics->getLast30DaysStats();
        
        $risks = [];

        // High lead time risk
        if ($demandStats['avg_lead_time'] > 14) {
            $risks[] = [
                'type' => 'high_lead_time',
                'severity' => 'high',
                'message' => 'Average lead time exceeds 2 weeks',
                'impact' => 'Delayed deliveries and customer dissatisfaction'
            ];
        }

        // Low throughput risk
        if ($demandStats['total'] < 10) {
            $risks[] = [
                'type' => 'low_throughput',
                'severity' => 'medium',
                'message' => 'Low ticket completion rate',
                'impact' => 'Backlog accumulation'
            ];
        }

        return [
            'total_risks' => count($risks),
            'risks' => $risks,
            'overall_health' => count($risks) == 0 ? 'healthy' : (count($risks) > 2 ? 'critical' : 'warning')
        ];
    }

    /**
     * Generate actionable recommendations
     */
    public function generateRecommendations(): array
    {
        $recommendations = [];

        // SLA recommendations
        $slaRisk = $this->predictSLABreachRisk();
        if ($slaRisk['risk_level'] !== 'low') {
            $recommendations[] = [
                'category' => 'SLA',
                'priority' => $slaRisk['risk_level'] === 'critical' ? 'urgent' : 'high',
                'title' => 'SLA Breach Risk Detected',
                'action' => $slaRisk['recommendation'],
                'expected_impact' => 'Reduce SLA breaches by 50%'
            ];
        }

        // Capacity recommendations
        $capacity = $this->predictCapacityIssues();
        if ($capacity['status'] !== 'healthy') {
            $recommendations[] = [
                'category' => 'Capacity',
                'priority' => $capacity['status'] === 'critical' ? 'urgent' : 'medium',
                'title' => 'Team Capacity Issue',
                'action' => $capacity['recommendation'],
                'expected_impact' => 'Improve lead time by 30%'
            ];
        }

        // Volume recommendations
        $volumePrediction = $this->predictNextMonthVolume();
        if ($volumePrediction['trend'] === 'increasing') {
            $recommendations[] = [
                'category' => 'Volume',
                'priority' => 'medium',
                'title' => 'Increasing Ticket Volume',
                'action' => 'Prepare for ' . $volumePrediction['predicted_volume'] . ' tickets next month',
                'expected_impact' => 'Proactive resource allocation'
            ];
        }

        return $recommendations;
    }

    private function getSLARecommendation(string $riskLevel): string
    {
        return match($riskLevel) {
            'critical' => 'URGENT: Immediately review all open tickets and reassign workload',
            'high' => 'Review ticket priorities and consider escalation procedures',
            'medium' => 'Monitor SLA compliance closely and optimize workflows',
            default => 'Maintain current SLA management practices'
        };
    }
}
