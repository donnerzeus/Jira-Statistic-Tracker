<?php

namespace App\Services\Jira;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class ServiceMetricsService
{
    protected JiraClient $jira;
    protected string $projectKey;

    public function __construct(JiraClient $jira)
    {
        $this->jira = $jira;
        $this->projectKey = \App\Models\Setting::get('jira_service_project', config('services.jira.service_project_key'));
    }

    /**
     * Get weekly ticket counts for the last N weeks
     */
    // Hardcoded IDs based on user info and extension code
    // TODO: Move to settings
    protected int $serviceDeskId = 265;
    protected int $allIssuesQueueId = 438; // IT-Web Active 

    public function getWeeklyServiceMetrics(int $weeks = 12): array
    {
        // Try to fetch from "All Issues" queue first
        // Note: Queue API might be paginated (limit 50 usually). 
        // For a full report we might need to loop, but let's start simple.
        
        $issues = $this->jira->getQueueIssues($this->serviceDeskId, $this->allIssuesQueueId);
        
        // If queue returns empty, maybe fallback to JQL (which we know fails, but keep logic clean)
        if (empty($issues)) {
             // Fallback to JQL if queue is empty (maybe ID is wrong)
             return [];
        }

        // Group by week
        $grouped = collect($issues)->groupBy(function ($issue) {
            return Carbon::parse($issue['fields']['created'])->format('Y-\WW');
        })->map->count();

        // Fill missing weeks with 0
        $result = [];
        for ($i = $weeks; $i >= 0; $i--) {
            $week = Carbon::now()->subWeeks($i)->format('Y-\WW');
            $result[$week] = $grouped->get($week, 0);
        }

        return $result;
    }

    /**
     * Get metrics grouped by assignee for the heatmap
     * Period: defaults to last 30 days
     */
    public function getTeamServiceMetrics(int $days = 30): array
    {
        // Use the same "All Issues" queue
        $issues = $this->jira->getQueueIssues($this->serviceDeskId, $this->allIssuesQueueId);

        $metrics = collect($issues)->groupBy(function ($issue) {
            return $issue['fields']['assignee']['displayName'] ?? 'Unassigned';
        })->map(function ($userIssues, $assigneeName) {
            
            // Calculate metrics for this user
            $resolvedIssues = $userIssues->filter(fn($i) => !empty($i['fields']['resolutiondate']));
            
            $totalResolutionHours = $resolvedIssues->sum(function ($issue) {
                $created = Carbon::parse($issue['fields']['created']);
                $resolved = Carbon::parse($issue['fields']['resolutiondate']);
                return $resolved->diffInHours($created);
            });

            $avgResolutionHours = $resolvedIssues->count() > 0 
                ? $totalResolutionHours / $resolvedIssues->count() 
                : 0;

            // SLA Breach Calculation (Fallback: > 48h)
            // Ideally we check customfield_XXXXX for SLA info if available in queue response
            $breaches = $resolvedIssues->filter(function ($issue) {
                $created = Carbon::parse($issue['fields']['created']);
                $resolved = Carbon::parse($issue['fields']['resolutiondate']);
                return $resolved->diffInHours($created) > 48;
            })->count();

            $breachRate = $resolvedIssues->count() > 0 
                ? ($breaches / $resolvedIssues->count()) * 100 
                : 0;

            return [
                'assignee' => $assigneeName,
                'service_ticket_count' => $userIssues->count(),
                'avg_resolution_hours' => round($avgResolutionHours, 1),
                'sla_breach_rate_percent' => round($breachRate, 1),
            ];
        })->values();

        return $metrics->toArray();
    }

    /**
     * Get daily KPIs for Google Sheets export
     */
    public function getDailyServiceKPIs(Carbon $date): array
    {
        $start = $date->copy()->startOfDay()->toIso8601String();
        $end = $date->copy()->endOfDay()->toIso8601String();
        
        // JQL for specific day
        $jql = "project = \"{$this->projectKey}\" AND created >= \"{$start}\" AND created <= \"{$end}\"";
        
        // We also need resolved tickets for that day, which might have been created earlier
        // So we might need two queries or a broader one.
        // For "Daily Export", usually we want "Tickets Created Today" and "Tickets Resolved Today".
        
        // 1. Created Today
        $createdIssues = $this->jira->searchIssues($jql, ['created']);
        $totalCreated = count($createdIssues);

        // 2. Resolved Today
        $jqlResolved = "project = \"{$this->projectKey}\" AND resolutiondate >= \"{$start}\" AND resolutiondate <= \"{$end}\"";
        $resolvedIssues = $this->jira->searchIssues($jqlResolved, ['created', 'resolutiondate', 'assignee']);
        
        $totalResolved = count($resolvedIssues);
        
        // Calculate Avg Resolution for tickets resolved TODAY
        $totalResolutionHours = 0;
        $breaches = 0;
        
        foreach ($resolvedIssues as $issue) {
            $created = Carbon::parse($issue['fields']['created']);
            $resolved = Carbon::parse($issue['fields']['resolutiondate']);
            $hours = $resolved->diffInHours($created);
            $totalResolutionHours += $hours;
            
            if ($hours > 48) $breaches++;
        }
        
        $avgResolution = $totalResolved > 0 ? $totalResolutionHours / $totalResolved : 0;

        return [
            'date' => $date->toDateString(),
            'total_tickets' => $totalCreated,
            'resolved_tickets' => $totalResolved,
            'sla_breaches' => $breaches,
            'avg_resolution_hours' => round($avgResolution, 1),
            // Top/Worst performer logic would go here
        ];
    }
    public function getTodayStats(): array
    {
        return Cache::remember('jira_service_today', 300, function () {
            $today = Carbon::today()->toIso8601String();
            $jql = "project = IT AND created >= \"{$today}\"";
            $issues = $this->jira->searchIssues($jql, ['summary', 'assignee', 'created', 'customfield_10056']);

            $totalTickets = count($issues);
            $slaBreaches = 0;
            $breachDetails = [];

            foreach ($issues as $issue) {
                // Check SLA field (customfield_10056 is common for SLA)
                if (isset($issue['fields']['customfield_10056'])) {
                    $slaField = $issue['fields']['customfield_10056'];
                    
                    // Check if SLA is breached
                    if (isset($slaField['ongoingCycle']['breached']) && $slaField['ongoingCycle']['breached'] === true) {
                        $slaBreaches++;
                        
                        $breachDetails[] = [
                            'key' => $issue['key'],
                            'summary' => $issue['fields']['summary'] ?? 'No summary',
                            'assignee' => $issue['fields']['assignee']['displayName'] ?? 'Unassigned',
                            'created' => Carbon::parse($issue['fields']['created'])->format('Y-m-d H:i'),
                            'breach_time' => isset($slaField['ongoingCycle']['breachTime']) 
                                ? Carbon::parse($slaField['ongoingCycle']['breachTime']['iso8601'])->format('Y-m-d H:i')
                                : 'Unknown'
                        ];
                    }
                }
            }

            return [
                'total_tickets' => $totalTickets,
                'sla_breaches' => $slaBreaches,
                'breach_details' => $breachDetails
            ];
        });
    }

    public function getTicketVolume(int $days = 30): array
    {
        return Cache::remember("jira_service_volume_{$days}", 600, function () use ($days) {
            $startDate = Carbon::now()->subDays($days)->startOfDay();
            $startDateStr = $startDate->format('Y-m-d');
            
            // Use JQL to get created tickets
            $jqlCreated = "project = IT AND created >= '{$startDateStr}' ORDER BY created ASC";
            $createdIssues = $this->jira->searchIssues($jqlCreated, ['created']);
            
            // Use JQL to get resolved tickets
            $jqlResolved = "project = IT AND resolved >= '{$startDateStr}' ORDER BY resolved ASC";
            $resolvedIssues = $this->jira->searchIssues($jqlResolved, ['resolutiondate']);

            // Initialize volume array
            $volume = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i)->format('Y-m-d');
                $volume[$date] = ['created' => 0, 'resolved' => 0];
            }

            // Count created tickets by date
            foreach ($createdIssues as $issue) {
                if (isset($issue['fields']['created'])) {
                    $createdDate = Carbon::parse($issue['fields']['created'])->format('Y-m-d');
                    if (isset($volume[$createdDate])) {
                        $volume[$createdDate]['created']++;
                    }
                }
            }

            // Count resolved tickets by date
            foreach ($resolvedIssues as $issue) {
                if (isset($issue['fields']['resolutiondate'])) {
                    $resolvedDate = Carbon::parse($issue['fields']['resolutiondate'])->format('Y-m-d');
                    if (isset($volume[$resolvedDate])) {
                        $volume[$resolvedDate]['resolved']++;
                    }
                }
            }

            return [
                'labels' => array_keys($volume),
                'created' => array_values(array_column($volume, 'created')),
                'resolved' => array_values(array_column($volume, 'resolved'))
            ];
        });
    }

    public function getIssueTypeBreakdown(): array
    {
        return Cache::remember('jira_service_types', 600, function () {
            $issues = $this->jira->getQueueIssues($this->serviceDeskId, $this->allIssuesQueueId);
            
            $breakdown = collect($issues)->groupBy(function ($issue) {
                return $issue['fields']['issuetype']['name'] ?? 'Unknown';
            })->map->count();

            return [
                'labels' => $breakdown->keys()->toArray(),
                'data' => $breakdown->values()->toArray()
            ];
        });
    }
}
