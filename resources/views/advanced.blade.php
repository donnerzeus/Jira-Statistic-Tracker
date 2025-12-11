@extends('layouts.app')

@section('content')
<div x-data="advancedAnalytics()" x-init="initAnalytics()" style="min-height: 100vh; background-color: var(--color-gray-50); padding-bottom: var(--spacing-2xl);">
    
    <!-- Header -->
    <div style="background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-light) 100%); color: var(--color-white); box-shadow: var(--shadow-xl);">
        <div style="max-width: 80rem; margin: 0 auto; padding: var(--spacing-2xl) var(--spacing-lg);">
            <div class="flex items-center justify-between">
                <div>
                    <h1 style="font-size: var(--font-size-3xl); font-weight: 700;">Advanced Analytics</h1>
                    <p style="color: rgba(255,255,255,0.9); margin-top: var(--spacing-sm); font-size: var(--font-size-base);">Deep insights into your Jira data</p>
                </div>
                <a href="{{ route('dashboard') }}" class="btn-secondary">
                    <svg style="height: 1rem; width: 1rem; margin-right: var(--spacing-sm); display: inline;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <div style="max-width: 80rem; margin: 0 auto; padding: var(--spacing-xl) var(--spacing-lg); display: flex; flex-direction: column; gap: var(--spacing-xl);">

        <!-- Worklog Analytics -->
        <div class="card" style="padding: var(--spacing-xl);" x-show="data.worklog">
            <h2 class="text-primary" style="font-size: var(--font-size-2xl); font-weight: 700; margin-bottom: var(--spacing-lg);">⏱️ Worklog Analytics</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-4" style="gap: var(--spacing-lg); margin-bottom: var(--spacing-xl);">
                <div style="text-align: center; padding: var(--spacing-lg); background-color: var(--color-gray-50); border-radius: var(--radius-lg);">
                    <div class="text-muted" style="font-size: var(--font-size-sm); margin-bottom: var(--spacing-xs);">Total Hours Logged</div>
                    <div class="text-primary" style="font-size: var(--font-size-4xl); font-weight: 700;" x-text="data.worklog.total_hours"></div>
                </div>
                <div style="text-align: center; padding: var(--spacing-lg); background-color: var(--color-gray-50); border-radius: var(--radius-lg);">
                    <div class="text-muted" style="font-size: var(--font-size-sm); margin-bottom: var(--spacing-xs);">Avg Hours/Issue</div>
                    <div class="text-primary" style="font-size: var(--font-size-4xl); font-weight: 700;" x-text="data.worklog.avg_hours_per_issue"></div>
                </div>
                <div style="text-align: center; padding: var(--spacing-lg); background-color: var(--color-gray-50); border-radius: var(--radius-lg);">
                    <div class="text-muted" style="font-size: var(--font-size-sm); margin-bottom: var(--spacing-xs);">Team Members</div>
                    <div class="text-primary" style="font-size: var(--font-size-4xl); font-weight: 700;" x-text="Object.keys(data.worklog.by_assignee || {}).length"></div>
                </div>
                <div style="text-align: center; padding: var(--spacing-lg); background-color: var(--color-gray-50); border-radius: var(--radius-lg);">
                    <div class="text-muted" style="font-size: var(--font-size-sm); margin-bottom: var(--spacing-xs);">Issue Types</div>
                    <div class="text-primary" style="font-size: var(--font-size-4xl); font-weight: 700;" x-text="Object.keys(data.worklog.by_type || {}).length"></div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2" style="gap: var(--spacing-xl);">
                <div>
                    <h3 style="font-size: var(--font-size-lg); font-weight: 600; margin-bottom: var(--spacing-md);">Hours by Assignee</h3>
                    <canvas id="worklogByAssigneeChart"></canvas>
                </div>
                <div>
                    <h3 style="font-size: var(--font-size-lg); font-weight: 600; margin-bottom: var(--spacing-md);">Hours by Type</h3>
                    <canvas id="worklogByTypeChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Priority & Component Analysis -->
        <div class="grid grid-cols-1 lg:grid-cols-2" style="gap: var(--spacing-xl);">
            <!-- Priority Distribution -->
            <div class="card" style="padding: var(--spacing-lg);" x-show="data.priority_distribution">
                <h2 class="text-primary" style="font-size: var(--font-size-xl); font-weight: 700; margin-bottom: var(--spacing-lg);">🎯 Priority Distribution</h2>
                <canvas id="priorityChart"></canvas>
                
                <div style="margin-top: var(--spacing-lg);">
                    <h4 style="font-size: var(--font-size-sm); font-weight: 600; margin-bottom: var(--spacing-sm);">Open Issues by Priority</h4>
                    <template x-for="(count, priority) in data.priority_distribution.open_by_priority" :key="priority">
                        <div class="flex justify-between items-center" style="padding: var(--spacing-sm); border-bottom: 1px solid var(--color-gray-200);">
                            <span style="font-size: var(--font-size-sm);" x-text="priority"></span>
                            <span class="badge-warning" x-text="count + ' open'"></span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Component & Label Analytics -->
            <div class="card" style="padding: var(--spacing-lg);" x-show="data.components_labels">
                <h2 class="text-primary" style="font-size: var(--font-size-xl); font-weight: 700; margin-bottom: var(--spacing-lg);">🏷️ Components & Labels</h2>
                
                <div style="margin-bottom: var(--spacing-xl);">
                    <h4 style="font-size: var(--font-size-sm); font-weight: 600; margin-bottom: var(--spacing-sm);">Top Components</h4>
                    <template x-for="(count, component) in data.components_labels.components" :key="component">
                        <div class="flex justify-between items-center" style="padding: var(--spacing-sm); border-bottom: 1px solid var(--color-gray-200);">
                            <span style="font-size: var(--font-size-sm);" x-text="component"></span>
                            <span class="badge-success" x-text="count"></span>
                        </div>
                    </template>
                </div>

                <div>
                    <h4 style="font-size: var(--font-size-sm); font-weight: 600; margin-bottom: var(--spacing-sm);">Top Labels</h4>
                    <div class="flex flex-wrap" style="gap: var(--spacing-sm);">
                        <template x-for="(count, label) in data.components_labels.labels" :key="label">
                            <span style="background-color: var(--color-info-light); color: var(--color-info); padding: var(--spacing-xs) var(--spacing-md); border-radius: var(--radius-full); font-size: var(--font-size-xs); font-weight: 600;">
                                <span x-text="label"></span> (<span x-text="count"></span>)
                            </span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resolution Time & Reopened Issues -->
        <div class="grid grid-cols-1 lg:grid-cols-2" style="gap: var(--spacing-xl);">
            <!-- Resolution Time by Type -->
            <div class="card" style="padding: var(--spacing-lg);" x-show="data.resolution_time_by_type">
                <h2 class="text-primary" style="font-size: var(--font-size-xl); font-weight: 700; margin-bottom: var(--spacing-lg);">⚡ Resolution Time by Type</h2>
                <canvas id="resolutionTimeChart"></canvas>
            </div>

            <!-- Reopened Issues -->
            <div class="card" style="padding: var(--spacing-lg);" x-show="data.reopened_issues">
                <h2 class="text-primary" style="font-size: var(--font-size-xl); font-weight: 700; margin-bottom: var(--spacing-lg);">🔄 Reopened Issues</h2>
                
                <div style="text-align: center; padding: var(--spacing-xl); background-color: var(--color-danger-light); border-radius: var(--radius-lg); margin-bottom: var(--spacing-lg);">
                    <div class="text-muted" style="font-size: var(--font-size-sm); margin-bottom: var(--spacing-xs);">Total Reopened</div>
                    <div style="font-size: var(--font-size-4xl); font-weight: 700; color: var(--color-danger);" x-text="data.reopened_issues.total_reopened"></div>
                </div>

                <div style="max-height: 20rem; overflow-y: auto;">
                    <template x-for="issue in data.reopened_issues.issues" :key="issue.key">
                        <div style="padding: var(--spacing-md); border-bottom: 1px solid var(--color-gray-200);">
                            <div class="flex justify-between items-start">
                                <div style="flex: 1;">
                                    <a :href="'https://sabanciuniv.atlassian.net/browse/' + issue.key" target="_blank" class="text-primary" style="font-weight: 600; font-size: var(--font-size-sm);" x-text="issue.key"></a>
                                    <p style="font-size: var(--font-size-xs); color: var(--color-gray-600); margin-top: var(--spacing-xs);" x-text="issue.summary"></p>
                                    <p class="text-muted" style="font-size: var(--font-size-xs); margin-top: var(--spacing-xs);" x-text="issue.assignee"></p>
                                </div>
                                <span class="badge-danger" x-text="issue.reopen_count + 'x'"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Sprint Velocity & Dependencies -->
        <div class="grid grid-cols-1 lg:grid-cols-2" style="gap: var(--spacing-xl);">
            <!-- Sprint Velocity -->
            <div class="card" style="padding: var(--spacing-lg);" x-show="data.sprint_velocity">
                <h2 class="text-primary" style="font-size: var(--font-size-xl); font-weight: 700; margin-bottom: var(--spacing-lg);">📈 Sprint Velocity</h2>
                
                <div class="grid grid-cols-2" style="gap: var(--spacing-md); margin-bottom: var(--spacing-lg);">
                    <div style="text-align: center; padding: var(--spacing-md); background-color: var(--color-gray-50); border-radius: var(--radius-lg);">
                        <div class="text-muted" style="font-size: var(--font-size-xs);">Average Velocity</div>
                        <div class="text-primary" style="font-size: var(--font-size-3xl); font-weight: 700;" x-text="data.sprint_velocity.average_velocity"></div>
                    </div>
                    <div style="text-align: center; padding: var(--spacing-md); background-color: var(--color-gray-50); border-radius: var(--radius-lg);">
                        <div class="text-muted" style="font-size: var(--font-size-xs);">Trend</div>
                        <div style="font-size: var(--font-size-3xl); font-weight: 700;"
                             :class="data.sprint_velocity.trend === 'up' ? 'text-success' : (data.sprint_velocity.trend === 'down' ? 'text-danger' : 'text-muted')"
                             x-text="data.sprint_velocity.trend === 'up' ? '↑' : (data.sprint_velocity.trend === 'down' ? '↓' : '→')"></div>
                    </div>
                </div>
                
                <canvas id="velocityChart"></canvas>
            </div>

            <!-- Dependencies -->
            <div class="card" style="padding: var(--spacing-lg);" x-show="data.dependencies">
                <h2 class="text-primary" style="font-size: var(--font-size-xl); font-weight: 700; margin-bottom: var(--spacing-lg);">🔗 Dependencies & Blockers</h2>
                
                <div class="grid grid-cols-2" style="gap: var(--spacing-md); margin-bottom: var(--spacing-lg);">
                    <div style="text-align: center; padding: var(--spacing-lg); background-color: var(--color-warning-light); border-radius: var(--radius-lg);">
                        <div class="text-muted" style="font-size: var(--font-size-sm);">Blocked Issues</div>
                        <div style="font-size: var(--font-size-3xl); font-weight: 700; color: var(--color-warning);" x-text="data.dependencies.total_blocked"></div>
                    </div>
                    <div style="text-align: center; padding: var(--spacing-lg); background-color: var(--color-info-light); border-radius: var(--radius-lg);">
                        <div class="text-muted" style="font-size: var(--font-size-sm);">Blocking Issues</div>
                        <div style="font-size: var(--font-size-3xl); font-weight: 700; color: var(--color-info);" x-text="data.dependencies.total_blocking"></div>
                    </div>
                </div>

                <div>
                    <h4 style="font-size: var(--font-size-sm); font-weight: 600; margin-bottom: var(--spacing-sm);">Blocked Issues</h4>
                    <template x-for="item in data.dependencies.blocked_issues" :key="item.key">
                        <div style="padding: var(--spacing-sm); border-bottom: 1px solid var(--color-gray-200); font-size: var(--font-size-sm);">
                            <span class="text-primary" x-text="item.key"></span> blocked by <span class="text-warning" x-text="item.blocked_by"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Comment Activity -->
        <div class="card" style="padding: var(--spacing-lg);" x-show="data.comment_activity">
            <h2 class="text-primary" style="font-size: var(--font-size-xl); font-weight: 700; margin-bottom: var(--spacing-lg);">💬 Comment Activity</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3" style="gap: var(--spacing-lg); margin-bottom: var(--spacing-lg);">
                <div style="text-align: center; padding: var(--spacing-lg); background-color: var(--color-gray-50); border-radius: var(--radius-lg);">
                    <div class="text-muted" style="font-size: var(--font-size-sm);">Total Comments</div>
                    <div class="text-primary" style="font-size: var(--font-size-3xl); font-weight: 700;" x-text="data.comment_activity.total_comments"></div>
                </div>
                <div style="text-align: center; padding: var(--spacing-lg); background-color: var(--color-gray-50); border-radius: var(--radius-lg);">
                    <div class="text-muted" style="font-size: var(--font-size-sm);">Issues with Comments</div>
                    <div class="text-primary" style="font-size: var(--font-size-3xl); font-weight: 700;" x-text="data.comment_activity.issues_with_comments"></div>
                </div>
                <div style="text-align: center; padding: var(--spacing-lg); background-color: var(--color-gray-50); border-radius: var(--radius-lg);">
                    <div class="text-muted" style="font-size: var(--font-size-sm);">Avg Comments/Issue</div>
                    <div class="text-primary" style="font-size: var(--font-size-3xl); font-weight: 700;" x-text="data.comment_activity.avg_comments_per_issue"></div>
                </div>
            </div>

            <div>
                <h4 style="font-size: var(--font-size-sm); font-weight: 600; margin-bottom: var(--spacing-sm);">Top Commenters</h4>
                <template x-for="(count, author) in data.comment_activity.top_commenters" :key="author">
                    <div class="flex justify-between items-center" style="padding: var(--spacing-sm); border-bottom: 1px solid var(--color-gray-200);">
                        <span style="font-size: var(--font-size-sm);" x-text="author"></span>
                        <span class="badge-success" x-text="count + ' comments'"></span>
                    </div>
                </template>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function advancedAnalytics() {
    return {
        data: {},
        charts: {},

        async initAnalytics() {
            const response = await fetch('/api/metrics/advanced');
            this.data = await response.json();
            
            this.$nextTick(() => {
                this.renderCharts();
            });
        },

        renderCharts() {
            if (this.data.worklog) {
                this.renderWorklogCharts();
            }
            if (this.data.priority_distribution) {
                this.renderPriorityChart();
            }
            if (this.data.resolution_time_by_type) {
                this.renderResolutionTimeChart();
            }
            if (this.data.sprint_velocity) {
                this.renderVelocityChart();
            }
        },

        renderWorklogCharts() {
            // By Assignee
            const assigneeCtx = document.getElementById('worklogByAssigneeChart').getContext('2d');
            this.charts.worklogAssignee = new Chart(assigneeCtx, {
                type: 'bar',
                data: {
                    labels: Object.keys(this.data.worklog.by_assignee),
                    datasets: [{
                        label: 'Hours',
                        data: Object.values(this.data.worklog.by_assignee),
                        backgroundColor: '#002D72'
                    }]
                },
                options: { responsive: true, maintainAspectRatio: true }
            });

            // By Type
            const typeCtx = document.getElementById('worklogByTypeChart').getContext('2d');
            this.charts.worklogType = new Chart(typeCtx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(this.data.worklog.by_type),
                    datasets: [{
                        data: Object.values(this.data.worklog.by_type),
                        backgroundColor: ['#002D72', '#10B981', '#F59E0B', '#EF4444', '#6366F1']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: true }
            });
        },

        renderPriorityChart() {
            const ctx = document.getElementById('priorityChart').getContext('2d');
            this.charts.priority = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: Object.keys(this.data.priority_distribution.total_distribution),
                    datasets: [{
                        data: Object.values(this.data.priority_distribution.total_distribution),
                        backgroundColor: ['#EF4444', '#F59E0B', '#10B981', '#3B82F6', '#6B7280']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: true }
            });
        },

        renderResolutionTimeChart() {
            const ctx = document.getElementById('resolutionTimeChart').getContext('2d');
            this.charts.resolutionTime = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: Object.keys(this.data.resolution_time_by_type),
                    datasets: [{
                        label: 'Avg Hours',
                        data: Object.values(this.data.resolution_time_by_type),
                        backgroundColor: '#F59E0B'
                    }]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: true,
                    scales: { y: { beginAtZero: true } }
                }
            });
        },

        renderVelocityChart() {
            const ctx = document.getElementById('velocityChart').getContext('2d');
            this.charts.velocity = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: Object.keys(this.data.sprint_velocity.sprints),
                    datasets: [{
                        label: 'Story Points',
                        data: Object.values(this.data.sprint_velocity.sprints),
                        borderColor: '#002D72',
                        backgroundColor: 'rgba(0, 45, 114, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: true,
                    scales: { y: { beginAtZero: true } }
                }
            });
        }
    }
}
</script>
@endsection
