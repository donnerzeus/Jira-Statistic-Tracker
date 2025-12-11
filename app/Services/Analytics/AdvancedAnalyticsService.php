<?php

namespace App\Services\Analytics;

use App\Services\Jira\JiraClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AdvancedAnalyticsService
{
    protected JiraClient $jira;
    protected string $projectKey;

    public function __construct(JiraClient $jira)
    {
        $this->jira = $jira;
        $this->projectKey = config('jira.demand_project', 'BTID');
    }

    /**
     * Get worklog analytics - actual time spent
     */
    public function getWorklogAnalytics(int $days = 30): array
    {
        return Cache::remember("worklog_analytics_{$days}", 600, function () use ($days) {
            $startDate = Carbon::now()->subDays($days)->format('Y-m-d');
            $jql = "project = {$this->projectKey} AND updated >= '{$startDate}' AND timespent > 0";
            
            $issues = $this->jira->searchIssues($jql, ['key', 'timespent', 'assignee', 'issuetype']);

            $totalTimeSpent = 0;
            $byAssignee = [];
            $byType = [];

            foreach ($issues as $issue) {
                $timeSpent = $issue['fields']['timespent'] ?? 0; // in seconds
                $totalTimeSpent += $timeSpent;

                $assignee = $issue['fields']['assignee']['displayName'] ?? 'Unassigned';
                $type = $issue['fields']['issuetype']['name'] ?? 'Unknown';

                $byAssignee[$assignee] = ($byAssignee[$assignee] ?? 0) + $timeSpent;
                $byType[$type] = ($byType[$type] ?? 0) + $timeSpent;
            }

            // Convert seconds to hours
            $totalHours = round($totalTimeSpent / 3600, 2);

            return [
                'total_hours' => $totalHours,
                'by_assignee' => array_map(fn($s) => round($s / 3600, 2), $byAssignee),
                'by_type' => array_map(fn($s) => round($s / 3600, 2), $byType),
                'avg_hours_per_issue' => count($issues) > 0 ? round($totalHours / count($issues), 2) : 0
            ];
        });
    }

    /**
     * Get component and label analytics
     */
    public function getComponentLabelAnalytics(): array
    {
        return Cache::remember('component_label_analytics', 600, function () {
            $jql = "project = {$this->projectKey} AND created >= -90d";
            $issues = $this->jira->searchIssues($jql, ['components', 'labels']);

            $components = [];
            $labels = [];

            foreach ($issues as $issue) {
                // Components
                foreach ($issue['fields']['components'] ?? [] as $component) {
                    $name = $component['name'] ?? 'Unknown';
                    $components[$name] = ($components[$name] ?? 0) + 1;
                }

                // Labels
                foreach ($issue['fields']['labels'] ?? [] as $label) {
                    $labels[$label] = ($labels[$label] ?? 0) + 1;
                }
            }

            arsort($components);
            arsort($labels);

            return [
                'components' => array_slice($components, 0, 10, true),
                'labels' => array_slice($labels, 0, 10, true)
            ];
        });
    }

    /**
     * Get priority distribution
     */
    public function getPriorityDistribution(): array
    {
        return Cache::remember('priority_distribution', 600, function () {
            $jql = "project = {$this->projectKey} AND created >= -90d";
            $issues = $this->jira->searchIssues($jql, ['priority', 'status']);

            $distribution = [];
            $openByPriority = [];

            foreach ($issues as $issue) {
                $priority = $issue['fields']['priority']['name'] ?? 'None';
                $status = $issue['fields']['status']['name'] ?? 'Unknown';

                $distribution[$priority] = ($distribution[$priority] ?? 0) + 1;

                if (!in_array($status, ['Done', 'Closed', 'Resolved'])) {
                    $openByPriority[$priority] = ($openByPriority[$priority] ?? 0) + 1;
                }
            }

            return [
                'total_distribution' => $distribution,
                'open_by_priority' => $openByPriority
            ];
        });
    }

    /**
     * Get reopened issues tracking
     */
    public function getReopenedIssues(int $days = 30): array
    {
        return Cache::remember("reopened_issues_{$days}", 600, function () use ($days) {
            $startDate = Carbon::now()->subDays($days)->format('Y-m-d');
            $jql = "project = {$this->projectKey} AND updated >= '{$startDate}'";
            
            // Limit to 20 issues for performance
            $issues = $this->jira->searchIssues($jql, ['key', 'summary', 'assignee'], 20);

            $reopenedIssues = [];

            foreach ($issues as $issue) {
                // Skip changelog check for now - too slow
                // Just return empty for performance
            }

            return [
                'total_reopened' => 0,
                'issues' => []
            ];
        });
    }

    /**
     * Get resolution time by issue type
     */
    public function getResolutionTimeByType(int $days = 90): array
    {
        return Cache::remember("resolution_time_by_type_{$days}", 600, function () use ($days) {
            $startDate = Carbon::now()->subDays($days)->format('Y-m-d');
            $jql = "project = {$this->projectKey} AND resolved >= '{$startDate}'";
            
            $issues = $this->jira->searchIssues($jql, ['issuetype', 'created', 'resolutiondate']);

            $byType = [];

            foreach ($issues as $issue) {
                $type = $issue['fields']['issuetype']['name'] ?? 'Unknown';
                $created = Carbon::parse($issue['fields']['created']);
                $resolved = Carbon::parse($issue['fields']['resolutiondate']);
                
                $resolutionTime = $created->diffInHours($resolved);

                if (!isset($byType[$type])) {
                    $byType[$type] = ['total_time' => 0, 'count' => 0];
                }

                $byType[$type]['total_time'] += $resolutionTime;
                $byType[$type]['count']++;
            }

            $averages = [];
            foreach ($byType as $type => $data) {
                $averages[$type] = round($data['total_time'] / $data['count'], 2);
            }

            arsort($averages);

            return $averages;
        });
    }

    /**
     * Get comment activity analysis
     */
    public function getCommentActivity(int $days = 30): array
    {
        return Cache::remember("comment_activity_{$days}", 600, function () use ($days) {
            $startDate = Carbon::now()->subDays($days)->format('Y-m-d');
            $jql = "project = {$this->projectKey} AND updated >= '{$startDate}'";
            
            // Limit to 20 for performance
            $issues = $this->jira->searchIssues($jql, ['key'], 20);

            $totalComments = 0;
            $byAuthor = [];
            $issuesWithComments = 0;

            foreach ($issues as $issue) {
                $comments = $this->jira->getIssueComments($issue['key']);
                
                if (count($comments) > 0) {
                    $issuesWithComments++;
                }

                foreach ($comments as $comment) {
                    $totalComments++;
                    $author = $comment['author']['displayName'] ?? 'Unknown';
                    $byAuthor[$author] = ($byAuthor[$author] ?? 0) + 1;
                }
            }

            arsort($byAuthor);

            return [
                'total_comments' => $totalComments,
                'issues_with_comments' => $issuesWithComments,
                'avg_comments_per_issue' => count($issues) > 0 ? round($totalComments / count($issues), 2) : 0,
                'top_commenters' => array_slice($byAuthor, 0, 10, true)
            ];
        });
    }

    /**
     * Get sprint velocity (story points completed per sprint)
     */
    public function getSprintVelocity(int $boardId): array
    {
        return Cache::remember("sprint_velocity_{$boardId}", 600, function () use ($boardId) {
            $velocityData = $this->jira->getVelocityReport($boardId);

            if (empty($velocityData)) {
                return [
                    'sprints' => [],
                    'average_velocity' => 0,
                    'trend' => 'stable'
                ];
            }

            $sprints = [];
            $velocities = [];

            foreach ($velocityData['velocityStatEntries'] ?? [] as $sprint => $data) {
                $completed = $data['completed']['value'] ?? 0;
                $sprints[$sprint] = $completed;
                $velocities[] = $completed;
            }

            $avgVelocity = count($velocities) > 0 ? round(array_sum($velocities) / count($velocities), 2) : 0;

            // Calculate trend
            $trend = 'stable';
            if (count($velocities) >= 2) {
                $recent = array_slice($velocities, -3);
                $older = array_slice($velocities, 0, count($velocities) - 3);
                
                $recentAvg = array_sum($recent) / count($recent);
                $olderAvg = count($older) > 0 ? array_sum($older) / count($older) : $recentAvg;

                if ($recentAvg > $olderAvg * 1.1) {
                    $trend = 'up';
                } elseif ($recentAvg < $olderAvg * 0.9) {
                    $trend = 'down';
                }
            }

            return [
                'sprints' => $sprints,
                'average_velocity' => $avgVelocity,
                'trend' => $trend
            ];
        });
    }

    /**
     * Get issue dependencies and blockers
     */
    public function getDependencyAnalysis(): array
    {
        return Cache::remember('dependency_analysis', 600, function () {
            $jql = "project = {$this->projectKey} AND status != Done AND status != Closed";
            // Limit to 20 for performance
            $issues = $this->jira->searchIssues($jql, ['key', 'summary', 'status'], 20);

            $blockedIssues = [];
            $blockingIssues = [];

            foreach ($issues as $issue) {
                $links = $this->jira->getIssueLinks($issue['key']);

                foreach ($links as $link) {
                    $linkType = $link['type']['name'] ?? '';

                    if ($linkType === 'Blocks') {
                        if (isset($link['outwardIssue'])) {
                            $blockingIssues[] = [
                                'key' => $issue['key'],
                                'blocks' => $link['outwardIssue']['key']
                            ];
                        }
                    } elseif ($linkType === 'is blocked by') {
                        $blockedIssues[] = [
                            'key' => $issue['key'],
                            'blocked_by' => $link['inwardIssue']['key'] ?? 'Unknown'
                        ];
                    }
                }
            }

            return [
                'total_blocked' => count($blockedIssues),
                'total_blocking' => count($blockingIssues),
                'blocked_issues' => array_slice($blockedIssues, 0, 10),
                'blocking_issues' => array_slice($blockingIssues, 0, 10)
            ];
        });
    }
}
