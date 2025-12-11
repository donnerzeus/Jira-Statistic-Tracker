<?php

namespace App\Services\Jira;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DemandMetricsService
{
    protected JiraClient $jira;
    protected string $projectKey;

    public function __construct(JiraClient $jira)
    {
        $this->jira = $jira;
        $this->projectKey = \App\Models\Setting::get('jira_demand_project', config('services.jira.demand_project_key'));
    }

    public function getWeeklyDemandMetrics(int $weeks = 12): array
    {
        // Handle multiple keys (e.g. "BTID, BTACA")
        $keys = array_map('trim', explode(',', $this->projectKey));
        $projectClause = count($keys) > 1 
            ? "project in (\"" . implode("\", \"", $keys) . "\")"
            : "project = \"{$keys[0]}\"";

        // Debug: Remove date filter to see if we get ANY issues
        // $jql = "{$projectClause} AND created >= -{$weeks}w ORDER BY created ASC";
        $jql = "{$projectClause} ORDER BY created DESC";
        
        // Limit to 50 for debug
        $issues = $this->jira->searchIssues($jql, ['created'], 50);

        $grouped = collect($issues)->groupBy(function ($issue) {
            return Carbon::parse($issue['fields']['created'])->format('Y-\WW');
        })->map->count();

        $result = [];
        $current = Carbon::now()->subWeeks($weeks)->startOfWeek();
        $end = Carbon::now()->endOfWeek();

        while ($current <= $end) {
            $key = $current->format('Y-\WW');
            $result[$key] = $grouped->get($key, 0);
            $current->addWeek();
        }

        return $result;
    }

    public function getTeamDemandMetrics(int $days = 30): array
    {
        $keys = array_map('trim', explode(',', $this->projectKey));
        $projectClause = count($keys) > 1 
            ? "project in (\"" . implode("\", \"", $keys) . "\")"
            : "project = \"{$keys[0]}\"";

        $jql = "{$projectClause} AND created >= -{$days}d";
        $issues = $this->jira->searchIssues($jql, [
            'assignee', 'created', 'resolutiondate', 'status', 'statuscategorychangedate'
        ]);

        $metrics = collect($issues)->groupBy(function ($issue) {
            return $issue['fields']['assignee']['displayName'] ?? 'Unassigned';
        })->map(function ($userIssues, $assigneeName) {
            if ($assigneeName === 'Unassigned') return null;

            $resolvedIssues = $userIssues->whereNotNull('fields.resolutiondate');

            // Lead Time: Resolution - Created
            $avgLeadTimeDays = $resolvedIssues->avg(function ($issue) {
                $created = Carbon::parse($issue['fields']['created']);
                $resolved = Carbon::parse($issue['fields']['resolutiondate']);
                return $resolved->diffInDays($created);
            }) ?? 0;

            // Cycle Time: Resolution - In Progress Date
            // Using statuscategorychangedate as a proxy for "started working" if status is Done.
            // This is an approximation.
            $avgCycleTimeDays = $resolvedIssues->avg(function ($issue) {
                $resolved = Carbon::parse($issue['fields']['resolutiondate']);
                $started = isset($issue['fields']['statuscategorychangedate']) 
                    ? Carbon::parse($issue['fields']['statuscategorychangedate']) 
                    : Carbon::parse($issue['fields']['created']);
                
                return $resolved->diffInDays($started);
            }) ?? 0;

            return [
                'assignee' => $assigneeName,
                'demand_ticket_count' => $userIssues->count(),
                'avg_lead_time_days' => round($avgLeadTimeDays, 1),
                'avg_cycle_time_days' => round($avgCycleTimeDays, 1),
            ];
        })->filter()->values();

        return $metrics->toArray();
    }

    public function getDailyDemandKPIs(Carbon $date): array
    {
        $start = $date->copy()->startOfDay()->toIso8601String();
        $end = $date->copy()->endOfDay()->toIso8601String();

        $keys = array_map('trim', explode(',', $this->projectKey));
        $projectClause = count($keys) > 1 
            ? "project in (\"" . implode("\", \"", $keys) . "\")"
            : "project = \"{$keys[0]}\"";

        // 1. Created Today (Total Demands)
        $jqlCreated = "{$projectClause} AND created >= \"{$start}\" AND created <= \"{$end}\"";
        $createdIssues = $this->jira->searchIssues($jqlCreated, ['created']);
        $totalDemands = count($createdIssues);

        // 2. Completed Today
        $jqlResolved = "{$projectClause} AND resolutiondate >= \"{$start}\" AND resolutiondate <= \"{$end}\"";
        $resolvedIssues = $this->jira->searchIssues($jqlResolved, [
            'created', 'resolutiondate', 'statuscategorychangedate'
        ]);
        $completedDemands = count($resolvedIssues);

        // 3. Avg Lead & Cycle Time for Completed Items
        $totalLeadTime = 0;
        $totalCycleTime = 0;

        foreach ($resolvedIssues as $issue) {
            $created = Carbon::parse($issue['fields']['created']);
            $resolved = Carbon::parse($issue['fields']['resolutiondate']);
            
            $started = isset($issue['fields']['statuscategorychangedate']) 
                ? Carbon::parse($issue['fields']['statuscategorychangedate']) 
                : $created;

            $totalLeadTime += $resolved->diffInDays($created);
            $totalCycleTime += $resolved->diffInDays($started);
        }

        $avgLeadTime = $completedDemands > 0 ? $totalLeadTime / $completedDemands : 0;
        $avgCycleTime = $completedDemands > 0 ? $totalCycleTime / $completedDemands : 0;

        // 4. Backlog Count (Status = Backlog)
        // Note: This is a snapshot of CURRENT backlog, not historical for that day (unless we use history search which is complex)
        $jqlBacklog = "{$projectClause} AND status = Backlog";
        $backlogIssues = $this->jira->searchIssues($jqlBacklog, ['created']);
        $backlogCount = count($backlogIssues);

        // 5. Oldest Backlog Age
        $oldestAge = 0;
        if ($backlogCount > 0) {
            // Issues are returned in order if we add ORDER BY, but searchIssues default sort depends on JQL
            // Let's force sort
            $jqlOldest = "{$projectClause} AND status = Backlog ORDER BY created ASC";
            $oldestIssues = $this->jira->searchIssues($jqlOldest, ['created'], 1);
            
            if (!empty($oldestIssues)) {
                $oldestCreated = Carbon::parse($oldestIssues[0]['fields']['created']);
                $oldestAge = $oldestCreated->diffInDays(Carbon::now());
            }
        }

        return [
            'date' => $date->toDateString(),
            'total_demands' => $totalDemands,
            'completed_demands' => $completedDemands,
            'avg_lead_time_days' => round($avgLeadTime, 1),
            'avg_cycle_time_days' => round($avgCycleTime, 1),
            'backlog_count' => $backlogCount,
            'oldest_backlog_age_days' => round($oldestAge, 1),
        ];
    }
    public function getLast30DaysStats(): array
    {
        return Cache::remember('jira_demand_last30', 300, function () {
            $keys = array_map('trim', explode(',', $this->projectKey));
            $projectClause = count($keys) > 1 
                ? "project in (\"" . implode("\", \"", $keys) . "\")"
                : "project = \"{$keys[0]}\"";

            $jql = "{$projectClause} AND created >= -30d";
            $issues = $this->jira->searchIssues($jql, ['created', 'resolutiondate']);
            
            $total = count($issues);
            
            $resolvedIssues = array_filter($issues, fn($i) => !empty($i['fields']['resolutiondate']));
            $totalLeadTime = 0;
            $countResolved = count($resolvedIssues);
            
            foreach ($resolvedIssues as $issue) {
                $created = Carbon::parse($issue['fields']['created']);
                $resolved = Carbon::parse($issue['fields']['resolutiondate']);
                // Ensure positive value
                $totalLeadTime += abs($resolved->diffInDays($created));
            }
            
            $avgLeadTime = $countResolved > 0 ? $totalLeadTime / $countResolved : 0;

            return [
                'total' => $total,
                'avg_lead_time' => round($avgLeadTime, 1)
            ];
        });
    }

    public function getThroughput(int $weeks = 12): array
    {
        return Cache::remember('jira_demand_throughput', 600, function () use ($weeks) {
            $keys = array_map('trim', explode(',', $this->projectKey));
            $projectClause = count($keys) > 1 
                ? "project in (\"" . implode("\", \"", $keys) . "\")"
                : "project = \"{$keys[0]}\"";

            $jql = "{$projectClause} AND resolutiondate >= -{$weeks}w";
            $issues = $this->jira->searchIssues($jql, ['resolutiondate']);

            $grouped = collect($issues)->groupBy(function ($issue) {
                return Carbon::parse($issue['fields']['resolutiondate'])->format('Y-\WW');
            })->map->count();

            $result = [];
            for ($i = $weeks; $i >= 0; $i--) {
                $week = Carbon::now()->subWeeks($i)->format('Y-\WW');
                $result[$week] = $grouped->get($week, 0);
            }

            return $result;
        });
    }

    public function getCycleTimeDistribution(int $days = 90): array
    {
        return Cache::remember('jira_demand_cycletime', 600, function () use ($days) {
            $keys = array_map('trim', explode(',', $this->projectKey));
            $projectClause = count($keys) > 1 
                ? "project in (\"" . implode("\", \"", $keys) . "\")"
                : "project = \"{$keys[0]}\"";

            $jql = "{$projectClause} AND resolutiondate >= -{$days}d";
            $issues = $this->jira->searchIssues($jql, ['created', 'resolutiondate', 'statuscategorychangedate']);

            $cycleTimes = [];
            foreach ($issues as $issue) {
                $resolved = Carbon::parse($issue['fields']['resolutiondate']);
                $started = isset($issue['fields']['statuscategorychangedate']) 
                    ? Carbon::parse($issue['fields']['statuscategorychangedate']) 
                    : Carbon::parse($issue['fields']['created']);
                
                $days = $resolved->diffInDays($started);
                $cycleTimes[] = floor($days);
            }

            $buckets = [
                '0-5 days' => 0,
                '6-10 days' => 0,
                '11-20 days' => 0,
                '21-30 days' => 0,
                '30+ days' => 0
            ];

            foreach ($cycleTimes as $time) {
                if ($time <= 5) $buckets['0-5 days']++;
                elseif ($time <= 10) $buckets['6-10 days']++;
                elseif ($time <= 20) $buckets['11-20 days']++;
                elseif ($time <= 30) $buckets['21-30 days']++;
                else $buckets['30+ days']++;
            }

            return [
                'labels' => array_keys($buckets),
                'data' => array_values($buckets)
            ];
        });
    }

    public function getBoardMetrics(int $boardId = 331): array
    {
        return Cache::remember('jira_board_metrics_' . $boardId, 300, function () use ($boardId) {
            // 1. Get Active Sprint
            $sprints = $this->jira->get("/rest/agile/1.0/board/{$boardId}/sprint?state=active");
            $activeSprint = $sprints['values'][0] ?? null;

            $sprintData = [
                'name' => 'No Active Sprint',
                'goal' => '',
                'total_issues' => 0,
                'completed_issues' => 0,
                'completion_rate' => 0,
                'status_breakdown' => []
            ];

            if ($activeSprint) {
                $sprintData['name'] = $activeSprint['name'];
                $sprintData['goal'] = $activeSprint['goal'] ?? '';
                
                $issues = $this->jira->get("/rest/agile/1.0/board/{$boardId}/sprint/{$activeSprint['id']}/issue?fields=status");
                $issueList = $issues['issues'] ?? [];
                
                $sprintData['total_issues'] = count($issueList);
                
                $breakdown = [
                    'To Do' => 0,
                    'In Progress' => 0,
                    'Done' => 0
                ];
                
                foreach ($issueList as $issue) {
                    $statusCat = $issue['fields']['status']['statusCategory']['name'] ?? 'To Do';
                    if (str_contains(strtolower($statusCat), 'done') || str_contains(strtolower($statusCat), 'complete')) {
                        $breakdown['Done']++;
                        $sprintData['completed_issues']++;
                    } elseif (str_contains(strtolower($statusCat), 'progress') || str_contains(strtolower($statusCat), 'indeterminate')) {
                        $breakdown['In Progress']++;
                    } else {
                        $breakdown['To Do']++;
                    }
                }
                
                $sprintData['status_breakdown'] = $breakdown;
                $sprintData['completion_rate'] = $sprintData['total_issues'] > 0 
                    ? round(($sprintData['completed_issues'] / $sprintData['total_issues']) * 100) 
                    : 0;
            }

            // 2. Get Backlog Count
            $keys = array_map('trim', explode(',', $this->projectKey));
            $projectClause = count($keys) > 1 
                ? "project in (\"" . implode("\", \"", $keys) . "\")"
                : "project = \"{$keys[0]}\"";
                
            $jqlBacklog = "{$projectClause} AND statusCategory = \"To Do\" AND sprint is EMPTY";
            
            $backlogCount = 0;
            $backlogResponse = $this->jira->get("/rest/api/3/search?jql=" . urlencode($jqlBacklog) . "&maxResults=0");
            if ($backlogResponse) {
                $backlogCount = $backlogResponse['total'] ?? 0;
            }

            return [
                'sprint' => $sprintData,
                'backlog_count' => $backlogCount
            ];
        });
    }

    public function getIssueTypeBreakdown(): array
    {
        return Cache::remember('jira_demand_types', 600, function () {
            $keys = array_map('trim', explode(',', $this->projectKey));
            $projectClause = count($keys) > 1 
                ? "project in (\"" . implode("\", \"", $keys) . "\")"
                : "project = \"{$keys[0]}\"";

            $jql = "{$projectClause} AND created >= -90d";
            $issues = $this->jira->searchIssues($jql, ['issuetype']);

            $breakdown = collect($issues)->groupBy(function ($issue) {
                return $issue['fields']['issuetype']['name'] ?? 'Unknown';
            })->map->count();

            return $breakdown->toArray();
        });
    }
}
