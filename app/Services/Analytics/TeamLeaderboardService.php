<?php

namespace App\Services\Analytics;

use App\Services\Jira\JiraClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class TeamLeaderboardService
{
    protected JiraClient $jira;
    protected string $projectKey;

    public function __construct(JiraClient $jira)
    {
        $this->jira = $jira;
        $this->projectKey = config('jira.demand_project', 'BTID');
    }

    /**
     * Get team leaderboard with gamification
     */
    public function getLeaderboard(int $days = 30): array
    {
        return Cache::remember("team_leaderboard_{$days}", 300, function () use ($days) {
            $startDate = Carbon::now()->subDays($days)->format('Y-m-d');
            $jql = "project = {$this->projectKey} AND resolved >= '{$startDate}'";
            
            $issues = $this->jira->searchIssues($jql, ['assignee', 'resolutiondate', 'created', 'priority', 'issuetype'], 200);

            $teamStats = [];

            foreach ($issues as $issue) {
                $assignee = $issue['fields']['assignee']['displayName'] ?? 'Unassigned';
                
                if ($assignee === 'Unassigned') continue;

                if (!isset($teamStats[$assignee])) {
                    $teamStats[$assignee] = [
                        'tickets_resolved' => 0,
                        'total_points' => 0,
                        'avg_resolution_time' => 0,
                        'resolution_times' => [],
                        'high_priority_count' => 0,
                        'badges' => []
                    ];
                }

                $teamStats[$assignee]['tickets_resolved']++;

                // Calculate resolution time
                $created = Carbon::parse($issue['fields']['created']);
                $resolved = Carbon::parse($issue['fields']['resolutiondate']);
                $resolutionHours = $created->diffInHours($resolved);
                $teamStats[$assignee]['resolution_times'][] = $resolutionHours;

                // Points system
                $points = 10; // Base points
                
                // Priority bonus
                $priority = $issue['fields']['priority']['name'] ?? 'Medium';
                if ($priority === 'Highest' || $priority === 'Critical') {
                    $points += 20;
                    $teamStats[$assignee]['high_priority_count']++;
                } elseif ($priority === 'High') {
                    $points += 10;
                }

                // Speed bonus (resolved in < 24 hours)
                if ($resolutionHours < 24) {
                    $points += 15;
                }

                $teamStats[$assignee]['total_points'] += $points;
            }

            // Calculate averages and assign badges
            foreach ($teamStats as $name => &$stats) {
                if (count($stats['resolution_times']) > 0) {
                    $stats['avg_resolution_time'] = round(array_sum($stats['resolution_times']) / count($stats['resolution_times']), 2);
                }
                unset($stats['resolution_times']);

                // Assign badges
                if ($stats['tickets_resolved'] >= 50) {
                    $stats['badges'][] = '🏆 Super Resolver';
                }
                if ($stats['high_priority_count'] >= 10) {
                    $stats['badges'][] = '🔥 Priority Master';
                }
                if ($stats['avg_resolution_time'] < 24) {
                    $stats['badges'][] = '⚡ Speed Demon';
                }
                if ($stats['total_points'] >= 500) {
                    $stats['badges'][] = '⭐ Top Performer';
                }
            }

            // Sort by total points
            uasort($teamStats, function($a, $b) {
                return $b['total_points'] <=> $a['total_points'];
            });

            // Add rank
            $rank = 1;
            $leaderboard = [];
            foreach ($teamStats as $name => $stats) {
                $leaderboard[] = array_merge(['rank' => $rank, 'name' => $name], $stats);
                $rank++;
            }

            return [
                'leaderboard' => array_slice($leaderboard, 0, 10),
                'total_members' => count($teamStats),
                'period_days' => $days
            ];
        });
    }

    /**
     * Get individual performance metrics
     */
    public function getIndividualMetrics(string $assigneeName, int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days)->format('Y-m-d');
        $jql = "project = {$this->projectKey} AND assignee = '{$assigneeName}' AND resolved >= '{$startDate}'";
        
        $issues = $this->jira->searchIssues($jql, ['resolutiondate', 'created', 'priority', 'status'], 100);

        $metrics = [
            'total_resolved' => count($issues),
            'by_priority' => [],
            'avg_resolution_hours' => 0,
            'fastest_resolution' => null,
            'slowest_resolution' => null
        ];

        $resolutionTimes = [];

        foreach ($issues as $issue) {
            $priority = $issue['fields']['priority']['name'] ?? 'None';
            $metrics['by_priority'][$priority] = ($metrics['by_priority'][$priority] ?? 0) + 1;

            $created = Carbon::parse($issue['fields']['created']);
            $resolved = Carbon::parse($issue['fields']['resolutiondate']);
            $hours = $created->diffInHours($resolved);
            $resolutionTimes[] = $hours;
        }

        if (count($resolutionTimes) > 0) {
            $metrics['avg_resolution_hours'] = round(array_sum($resolutionTimes) / count($resolutionTimes), 2);
            $metrics['fastest_resolution'] = min($resolutionTimes);
            $metrics['slowest_resolution'] = max($resolutionTimes);
        }

        return $metrics;
    }

    /**
     * Get team achievements
     */
    public function getTeamAchievements(): array
    {
        $leaderboard = $this->getLeaderboard(30);
        
        $achievements = [];

        // Team milestones
        $totalResolved = array_sum(array_column($leaderboard['leaderboard'], 'tickets_resolved'));
        
        if ($totalResolved >= 100) {
            $achievements[] = [
                'title' => '💯 Century Club',
                'description' => 'Team resolved 100+ tickets this month',
                'unlocked' => true
            ];
        }

        if ($totalResolved >= 500) {
            $achievements[] = [
                'title' => '🚀 Rocket Team',
                'description' => 'Team resolved 500+ tickets this month',
                'unlocked' => true
            ];
        }

        // Check if any member has all badges
        foreach ($leaderboard['leaderboard'] as $member) {
            if (count($member['badges']) >= 4) {
                $achievements[] = [
                    'title' => '👑 Master Achiever',
                    'description' => "{$member['name']} earned all badges",
                    'unlocked' => true
                ];
                break;
            }
        }

        return $achievements;
    }
}
