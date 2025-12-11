@extends('layouts.app')

@section('content')
<div x-data="dashboard()" x-init="initDashboard()" style="min-height: 100vh; background-color: var(--color-gray-50); padding-bottom: var(--spacing-2xl);">
    
    <!-- Notification Toast Container -->
    <div id="notification-container" style="position: fixed; top: 1rem; right: 1rem; z-index: 9999; display: flex; flex-direction: column; gap: var(--spacing-sm); max-width: 24rem;"></div>
    
    <!-- Header Section -->
    <div style="background-color: var(--color-primary); color: var(--color-white); box-shadow: var(--shadow-lg);">
        <div style="max-width: 80rem; margin: 0 auto; padding: var(--spacing-xl) var(--spacing-lg);">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div>
                    <h1 style="font-size: var(--font-size-3xl); font-weight: 700; letter-spacing: -0.025em;">Unified Analytics Portal</h1>
                    <p style="color: rgba(255,255,255,0.8); margin-top: var(--spacing-sm); font-size: var(--font-size-sm);">Real-time insights for IT Service & Demand Management</p>
                </div>
                <div class="flex space-x-3 mt-4 md:mt-0">
                    <button @click="refreshData()" :disabled="isLoading" class="btn-secondary">
                        <svg x-show="!isLoading" style="height: 1rem; width: 1rem; margin-right: var(--spacing-sm); display: inline;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        <svg x-show="isLoading" class="animate-spin" style="height: 1rem; width: 1rem; margin-right: var(--spacing-sm); display: inline; color: var(--color-primary);" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="isLoading ? 'Refreshing...' : 'Refresh Data'"></span>
                    </button>
                    <button @click="exportToPDF()" class="btn-primary">
                        <svg style="height: 1rem; width: 1rem; margin-right: var(--spacing-sm); display: inline;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Export Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div style="background-color: var(--color-white); border-bottom: 1px solid var(--color-gray-200); box-shadow: var(--shadow-sm);">
        <div style="max-width: 80rem; margin: 0 auto; padding: var(--spacing-md) var(--spacing-lg);">
            <div class="flex flex-col md:flex-row justify-between items-center" style="gap: var(--spacing-md);">
                <div class="flex items-center" style="gap: var(--spacing-sm);">
                    <svg style="height: 1.25rem; width: 1.25rem; color: var(--color-gray-500);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span class="text-muted" style="font-size: var(--font-size-sm); font-weight: 500;">Date Range:</span>
                </div>
                <div class="flex flex-wrap" style="gap: var(--spacing-sm);">
                    <button @click="setDateRange(7)" :class="dateRange === 7 ? 'btn-primary' : 'btn-secondary'" style="font-size: var(--font-size-sm); padding: var(--spacing-xs) var(--spacing-md);">1 Week</button>
                    <button @click="setDateRange(30)" :class="dateRange === 30 ? 'btn-primary' : 'btn-secondary'" style="font-size: var(--font-size-sm); padding: var(--spacing-xs) var(--spacing-md);">1 Month</button>
                    <button @click="setDateRange(90)" :class="dateRange === 90 ? 'btn-primary' : 'btn-secondary'" style="font-size: var(--font-size-sm); padding: var(--spacing-xs) var(--spacing-md);">3 Months</button>
                    <button @click="setDateRange(180)" :class="dateRange === 180 ? 'btn-primary' : 'btn-secondary'" style="font-size: var(--font-size-sm); padding: var(--spacing-xs) var(--spacing-md);">6 Months</button>
                    <button @click="setDateRange(365)" :class="dateRange === 365 ? 'btn-primary' : 'btn-secondary'" style="font-size: var(--font-size-sm); padding: var(--spacing-xs) var(--spacing-md);">1 Year</button>
                </div>
                <div class="text-muted" style="font-size: var(--font-size-xs);">
                    <span x-text="getDateRangeText()"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- SLA Breach Details Modal -->
    <div x-show="showBreachModal" @click.away="showBreachModal = false" style="position: fixed; inset: 0; background-color: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 50; padding: var(--spacing-lg);" x-cloak>
        <div @click.stop class="card" style="max-width: 60rem; width: 100%; max-height: 80vh; overflow-y: auto; padding: var(--spacing-xl);">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h3 class="text-primary" style="font-size: var(--font-size-2xl); font-weight: 700;">SLA Breach Details</h3>
                    <p class="text-muted" style="font-size: var(--font-size-sm); margin-top: var(--spacing-xs);">
                        <span x-text="stats.service.breach_details ? stats.service.breach_details.length : 0"></span> ticket(s) with SLA breaches
                    </p>
                </div>
                <button @click="showBreachModal = false" style="color: var(--color-gray-400); hover:color: var(--color-gray-600);">
                    <svg style="height: 1.5rem; width: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div style="overflow-x: auto;">
                <table style="min-width: 100%; border-collapse: separate; border-spacing: 0;">
                    <thead style="background-color: var(--color-gray-50);">
                        <tr>
                            <th style="padding: var(--spacing-md); text-align: left; font-size: var(--font-size-xs); font-weight: 700; color: var(--color-gray-500); text-transform: uppercase;">Ticket</th>
                            <th style="padding: var(--spacing-md); text-align: left; font-size: var(--font-size-xs); font-weight: 700; color: var(--color-gray-500); text-transform: uppercase;">Summary</th>
                            <th style="padding: var(--spacing-md); text-align: left; font-size: var(--font-size-xs); font-weight: 700; color: var(--color-gray-500); text-transform: uppercase;">Assignee</th>
                            <th style="padding: var(--spacing-md); text-align: left; font-size: var(--font-size-xs); font-weight: 700; color: var(--color-gray-500); text-transform: uppercase;">Created</th>
                            <th style="padding: var(--spacing-md); text-align: left; font-size: var(--font-size-xs); font-weight: 700; color: var(--color-gray-500); text-transform: uppercase;">Breach Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="breach in stats.service.breach_details" :key="breach.key">
                            <tr style="border-top: 1px solid var(--color-gray-200);">
                                <td style="padding: var(--spacing-md); white-space: nowrap;">
                                    <a :href="'https://sabanciuniv.atlassian.net/browse/' + breach.key" target="_blank" class="text-primary" style="font-weight: 600; font-size: var(--font-size-sm);" x-text="breach.key"></a>
                                </td>
                                <td style="padding: var(--spacing-md); font-size: var(--font-size-sm);" x-text="breach.summary"></td>
                                <td style="padding: var(--spacing-md); white-space: nowrap; font-size: var(--font-size-sm);" x-text="breach.assignee"></td>
                                <td style="padding: var(--spacing-md); white-space: nowrap; font-size: var(--font-size-sm); color: var(--color-gray-600);" x-text="breach.created"></td>
                                <td style="padding: var(--spacing-md); white-space: nowrap;">
                                    <span class="badge-danger" x-text="breach.breach_time"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div style="max-width: 80rem; margin: 0 auto; padding: var(--spacing-xl) var(--spacing-lg); display: flex; flex-direction: column; gap: var(--spacing-xl);">

        <!-- Executive Summary -->
        <div class="card" style="background: linear-gradient(to right, var(--color-primary), var(--color-primary-light)); padding: var(--spacing-xl); color: var(--color-white);" x-show="executiveSummary">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 style="font-size: var(--font-size-2xl); font-weight: 700;">Executive Summary</h2>
                    <p style="color: rgba(255,255,255,0.8); font-size: var(--font-size-sm); margin-top: var(--spacing-xs);">Real-time Performance Overview</p>
                </div>
                <div class="text-right">
                    <div style="font-size: var(--font-size-sm); color: rgba(255,255,255,0.8);">Last Updated</div>
                    <div style="font-size: var(--font-size-lg); font-weight: 600;" x-text="new Date().toLocaleTimeString('tr-TR', {hour: '2-digit', minute: '2-digit'})"></div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div style="background-color: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: var(--radius-lg); padding: var(--spacing-lg); border: 1px solid rgba(255,255,255,0.2);">
                    <div class="flex items-center justify-between">
                        <div>
                            <p style="color: rgba(255,255,255,0.8); font-size: var(--font-size-sm); font-weight: 500;">Active Tickets</p>
                            <p style="font-size: var(--font-size-4xl); font-weight: 700; margin-top: var(--spacing-sm);" x-text="executiveSummary.summary.total_active_tickets"></p>
                        </div>
                        <div style="height: 3rem; width: 3rem; background-color: rgba(255,255,255,0.2); border-radius: var(--radius-full); display: flex; align-items: center; justify-content: center;">
                            <svg style="height: 1.5rem; width: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        </div>
                    </div>
                </div>

                <div style="background-color: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: var(--radius-lg); padding: var(--spacing-lg); border: 1px solid rgba(255,255,255,0.2);">
                    <div class="flex items-center justify-between">
                        <div>
                            <p style="color: rgba(255,255,255,0.8); font-size: var(--font-size-sm); font-weight: 500;">SLA Compliance</p>
                            <p style="font-size: var(--font-size-4xl); font-weight: 700; margin-top: var(--spacing-sm);" x-text="executiveSummary.summary.sla_compliance_rate + '%'"></p>
                        </div>
                        <div style="height: 3rem; width: 3rem; border-radius: var(--radius-full); display: flex; align-items: center; justify-content: center;"
                             :style="'background-color: ' + (executiveSummary.summary.sla_compliance_rate >= 95 ? 'rgba(16, 185, 129, 0.3)' : (executiveSummary.summary.sla_compliance_rate >= 93 ? 'rgba(245, 158, 11, 0.3)' : 'rgba(239, 68, 68, 0.3)'))">
                            <svg style="height: 1.5rem; width: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>
                </div>

                <div style="background-color: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: var(--radius-lg); padding: var(--spacing-lg); border: 1px solid rgba(255,255,255,0.2);">
                    <div class="flex items-center justify-between">
                        <div>
                            <p style="color: rgba(255,255,255,0.8); font-size: var(--font-size-sm); font-weight: 500;">Team Performance</p>
                            <p style="font-size: var(--font-size-4xl); font-weight: 700; margin-top: var(--spacing-sm);" x-text="executiveSummary.summary.team_performance_score"></p>
                        </div>
                        <div style="height: 3rem; width: 3rem; border-radius: var(--radius-full); display: flex; align-items: center; justify-content: center;"
                             :style="'background-color: ' + (executiveSummary.summary.team_performance_score >= 80 ? 'rgba(16, 185, 129, 0.3)' : (executiveSummary.summary.team_performance_score >= 60 ? 'rgba(245, 158, 11, 0.3)' : 'rgba(239, 68, 68, 0.3)'))">
                            <svg style="height: 1.5rem; width: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                    </div>
                </div>

                <div style="background-color: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: var(--radius-lg); padding: var(--spacing-lg); border: 1px solid rgba(255,255,255,0.2);">
                    <div class="flex items-center justify-between">
                        <div>
                            <p style="color: rgba(255,255,255,0.8); font-size: var(--font-size-sm); font-weight: 500;">Overall Trend</p>
                            <p style="font-size: var(--font-size-4xl); font-weight: 700; margin-top: var(--spacing-sm); text-transform: capitalize;" x-text="executiveSummary.summary.trend"></p>
                        </div>
                        <div style="height: 3rem; width: 3rem; background-color: rgba(255,255,255,0.2); border-radius: var(--radius-full); display: flex; align-items: center; justify-content: center;">
                            <svg x-show="executiveSummary.summary.trend === 'up'" style="height: 1.5rem; width: 1.5rem; color: #10B981;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                            <svg x-show="executiveSummary.summary.trend === 'down'" style="height: 1.5rem; width: 1.5rem; color: #EF4444;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                            <svg x-show="executiveSummary.summary.trend === 'stable'" style="height: 1.5rem; width: 1.5rem; color: #60A5FA;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path></svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Critical Alerts -->
        <div class="card" style="padding: var(--spacing-lg); border-left: 4px solid var(--color-danger);" x-show="executiveSummary && executiveSummary.critical_alerts.length > 0">
            <div class="flex items-center mb-4">
                <svg style="height: 1.5rem; width: 1.5rem; color: var(--color-danger); margin-right: var(--spacing-sm);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <h3 style="font-size: var(--font-size-lg); font-weight: 700; color: var(--color-gray-900);">Requires Immediate Attention</h3>
            </div>
            <div style="display: flex; flex-direction: column; gap: var(--spacing-md);">
                <template x-for="alert in executiveSummary.critical_alerts" :key="alert.type">
                    <div class="flex items-start" style="padding: var(--spacing-md); border-radius: var(--radius-lg); border: 1px solid;"
                         :style="'background-color: ' + (alert.severity === 'high' ? 'var(--color-danger-light)' : 'var(--color-warning-light)') + '; border-color: ' + (alert.severity === 'high' ? 'var(--color-danger)' : 'var(--color-warning)')">
                        <div class="flex-shrink-0">
                            <span :class="alert.severity === 'high' ? 'badge-danger' : 'badge-warning'" x-text="alert.severity.toUpperCase()"></span>
                        </div>
                        <div style="margin-left: var(--spacing-md); flex: 1;">
                            <p style="font-size: var(--font-size-sm); font-weight: 500; color: var(--color-gray-900);" x-text="alert.message"></p>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Auto-Refresh Indicator -->
        <div class="flex items-center justify-between" style="padding: var(--spacing-md); background-color: var(--color-white); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
            <div class="flex items-center" style="gap: var(--spacing-sm);">
                <div class="animate-pulse" style="height: 0.5rem; width: 0.5rem; border-radius: var(--radius-full); background-color: var(--color-success);"></div>
                <span class="text-muted" style="font-size: var(--font-size-sm);">Live Dashboard</span>
                <span style="font-size: var(--font-size-xs); color: var(--color-gray-400);">•</span>
                <span class="text-muted" style="font-size: var(--font-size-xs);">Auto-refresh every 5 minutes</span>
            </div>
            <div class="text-muted" style="font-size: var(--font-size-xs);">
                Last updated: <span x-text="getTimeSinceRefresh()"></span>
            </div>
        </div>

        <!-- Performance Insights -->
        <div class="card" style="padding: var(--spacing-lg);" x-show="insights.length > 0">
            <div class="flex items-center mb-4" style="gap: var(--spacing-sm);">
                <svg style="height: 1.5rem; width: 1.5rem; color: var(--color-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                <h3 class="text-primary" style="font-size: var(--font-size-lg); font-weight: 700;">Performance Insights</h3>
                <span class="badge-success" style="margin-left: auto;" x-text="insights.length + ' insights'"></span>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(20rem, 1fr)); gap: var(--spacing-md);">
                <template x-for="insight in insights" :key="insight.title">
                    <div style="padding: var(--spacing-md); border-radius: var(--radius-lg); border: 1px solid;"
                         :style="'background-color: ' + (insight.type === 'critical' ? 'var(--color-danger-light)' : insight.type === 'warning' ? 'var(--color-warning-light)' : insight.type === 'success' ? 'var(--color-success-light)' : 'var(--color-info-light)') + '; border-color: ' + (insight.type === 'critical' ? 'var(--color-danger)' : insight.type === 'warning' ? 'var(--color-warning)' : insight.type === 'success' ? 'var(--color-success)' : 'var(--color-info)')">
                        <div class="flex items-start" style="gap: var(--spacing-sm);">
                            <div style="flex-shrink: 0; height: 2rem; width: 2rem; border-radius: var(--radius-full); display: flex; align-items: center; justify-content: center;"
                                 :style="'background-color: ' + (insight.type === 'critical' ? 'var(--color-danger)' : insight.type === 'warning' ? 'var(--color-warning)' : insight.type === 'success' ? 'var(--color-success)' : 'var(--color-info)') + '; color: white;'">
                                <svg x-show="insight.icon === 'alert'" style="height: 1rem; width: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <svg x-show="insight.icon === 'warning'" style="height: 1rem; width: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <svg x-show="insight.icon === 'check'" style="height: 1rem; width: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <svg x-show="insight.icon === 'info'" style="height: 1rem; width: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <svg x-show="insight.icon === 'clock'" style="height: 1rem; width: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div style="flex: 1;">
                                <h4 style="font-size: var(--font-size-sm); font-weight: 700; color: var(--color-gray-900); margin-bottom: var(--spacing-xs);" x-text="insight.title"></h4>
                                <p style="font-size: var(--font-size-xs); color: var(--color-gray-700); margin-bottom: var(--spacing-sm);" x-text="insight.message"></p>
                                <div style="font-size: var(--font-size-xs); font-weight: 600;"
                                     :style="'color: ' + (insight.type === 'critical' ? 'var(--color-danger)' : insight.type === 'warning' ? 'var(--color-warning)' : insight.type === 'success' ? 'var(--color-success)' : 'var(--color-info)')"
                                     x-text="'→ ' + insight.action"></div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Top Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3" style="gap: var(--spacing-lg);">
            <!-- Service Card -->
            <div class="card card-hover" style="padding: var(--spacing-lg); border-left: 4px solid var(--color-primary);">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="flex items-center">
                        <div style="padding: var(--spacing-md); border-radius: var(--radius-full); background-color: rgba(0, 45, 114, 0.1); color: var(--color-primary);">
                            <svg style="height: 2rem; width: 2rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        </div>
                        <div style="margin-left: var(--spacing-md);">
                            <p class="text-muted" style="font-size: var(--font-size-sm); font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em;">Service Today</p>
                            <p style="font-size: var(--font-size-3xl); font-weight: 700; color: var(--color-gray-900); margin-top: var(--spacing-xs);" x-text="stats.service.total_tickets || 0"></p>
                        </div>
                    </div>
                </div>
                <div style="margin-top: var(--spacing-md); display: flex; align-items: center; justify-between;">
                    <span class="text-muted" style="font-size: var(--font-size-xs);">Incoming Tickets</span>
                    <span @click="stats.service.sla_breaches > 0 ? showBreachModal = true : null" :class="stats.service.sla_breaches > 0 ? 'badge-danger cursor-pointer hover:opacity-80' : 'badge-success'" x-text="stats.service.sla_breaches > 0 ? stats.service.sla_breaches + ' Breaches' : 'No Breaches'"></span>
                </div>
                <div style="background-color: var(--color-gray-50); margin: var(--spacing-md) calc(-1 * var(--spacing-lg)) calc(-1 * var(--spacing-lg)); padding: var(--spacing-md) var(--spacing-lg); border-top: 1px solid var(--color-gray-200);">
                    <a href="https://sabanciuniv.atlassian.net/jira/servicedesk/projects/IT/queues/custom/438" target="_blank" class="text-primary" style="font-size: var(--font-size-sm); font-weight: 500; display: flex; align-items: center;">
                        View active queue 
                        <svg style="margin-left: var(--spacing-xs); height: 1rem; width: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </a>
                </div>
            </div>

            <!-- Demand Card -->
            <div class="card card-hover" style="padding: var(--spacing-lg); border-left: 4px solid #9333EA;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="flex items-center">
                        <div style="padding: var(--spacing-md); border-radius: var(--radius-full); background-color: rgba(147, 51, 234, 0.1); color: #9333EA;">
                            <svg style="height: 2rem; width: 2rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        </div>
                        <div style="margin-left: var(--spacing-md);">
                            <p class="text-muted" style="font-size: var(--font-size-sm); font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em;">Demand (30 Days)</p>
                            <p style="font-size: var(--font-size-3xl); font-weight: 700; color: var(--color-gray-900); margin-top: var(--spacing-xs);" x-text="stats.demand.total || 0"></p>
                        </div>
                    </div>
                </div>
                <div style="margin-top: var(--spacing-md); display: flex; align-items: center; justify-between;">
                    <span class="text-muted" style="font-size: var(--font-size-xs);">Avg Lead Time</span>
                    <span style="font-size: var(--font-size-2xl); font-weight: 600; color: var(--color-gray-700);" x-text="stats.demand.avg_lead_time + ' days'"></span>
                </div>
                <div style="background-color: var(--color-gray-50); margin: var(--spacing-md) calc(-1 * var(--spacing-lg)) calc(-1 * var(--spacing-lg)); padding: var(--spacing-md) var(--spacing-lg); border-top: 1px solid var(--color-gray-200);">
                    <a href="https://app.suticket.sabanciuniv.edu/jira/software/c/projects/BTID/boards/331" target="_blank" style="font-size: var(--font-size-sm); font-weight: 500; color: #9333EA; display: flex; align-items: center;">
                        View demand backlog
                        <svg style="margin-left: var(--spacing-xs); height: 1rem; width: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </a>
                </div>
            </div>

            <!-- Forecast Card -->
            <div class="card card-hover" style="padding: var(--spacing-lg); border-left: 4px solid var(--color-success);">
                <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                    <div class="flex items-center">
                        <div style="padding: var(--spacing-md); border-radius: var(--radius-full); background-color: var(--color-success-light); color: var(--color-success);">
                            <svg style="height: 2rem; width: 2rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        </div>
                        <div style="margin-left: var(--spacing-md);">
                            <p class="text-muted" style="font-size: var(--font-size-sm); font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em;">Forecast Next Month</p>
                            <p style="font-size: var(--font-size-3xl); font-weight: 700; color: var(--color-gray-900); margin-top: var(--spacing-xs);" x-text="forecast.next_month_ticket_forecast || 0"></p>
                        </div>
                    </div>
                </div>
                <div style="margin-top: var(--spacing-md); display: flex; align-items: center; justify-between;">
                    <span class="text-muted" style="font-size: var(--font-size-xs);">Based on SLA trends</span>
                    <span :class="riskLevel === 'High' ? 'badge-danger' : (riskLevel === 'Medium' ? 'badge-warning' : 'badge-success')" x-text="riskLevel + ' Risk'"></span>
                </div>
                <div style="background-color: var(--color-gray-50); margin: var(--spacing-md) calc(-1 * var(--spacing-lg)) calc(-1 * var(--spacing-lg)); padding: var(--spacing-md) var(--spacing-lg); border-top: 1px solid var(--color-gray-200);">
                    <a href="#" class="text-success" style="font-size: var(--font-size-sm); font-weight: 500; display: flex; align-items: center;">
                        View detailed forecast
                        <svg style="margin-left: var(--spacing-xs); height: 1rem; width: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Agile Board Overview -->
        <div class="card" style="padding: var(--spacing-xl);" x-show="boardMetrics">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
                <div>
                    <h3 class="text-primary" style="font-size: var(--font-size-xl); font-weight: 700;">Agile Board Overview (BTID)</h3>
                    <p class="text-muted" style="font-size: var(--font-size-sm); margin-top: var(--spacing-xs); display: flex; align-items: center;">
                        <svg style="height: 1rem; width: 1rem; margin-right: var(--spacing-xs);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <span x-text="boardMetrics.sprint.name"></span>
                    </p>
                </div>
                <div class="mt-4 md:mt-0 flex items-center" style="gap: var(--spacing-xl);">
                    <div class="text-right">
                        <span class="text-muted" style="display: block; font-size: var(--font-size-xs); text-transform: uppercase; letter-spacing: 0.05em;">Sprint Goal</span>
                        <span style="display: block; font-size: var(--font-size-sm); font-weight: 500; color: var(--color-gray-900);" x-text="boardMetrics.sprint.goal || 'No goal set'"></span>
                    </div>
                    <div style="height: 3rem; width: 1px; background-color: var(--color-gray-200);"></div>
                    <div class="text-right">
                        <span class="text-muted" style="display: block; font-size: var(--font-size-xs); text-transform: uppercase; letter-spacing: 0.05em;">Backlog Size</span>
                        <span class="text-primary" style="display: block; font-size: var(--font-size-3xl); font-weight: 700;" x-text="boardMetrics.backlog_count"></span>
                    </div>
                </div>
            </div>

            <!-- Sprint Progress -->
            <div style="margin-bottom: var(--spacing-xl);">
                <div class="flex justify-between" style="font-size: var(--font-size-sm); font-weight: 500; color: var(--color-gray-900); margin-bottom: var(--spacing-sm);">
                    <span>Sprint Progress</span>
                    <span x-text="boardMetrics.sprint.completion_rate + '%'"></span>
                </div>
                <div style="width: 100%; background-color: var(--color-gray-100); border-radius: var(--radius-full); height: 0.75rem; box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);">
                    <div style="background-color: var(--color-primary); height: 0.75rem; border-radius: var(--radius-full); transition: width 1s ease-out;" :style="'width: ' + boardMetrics.sprint.completion_rate + '%'"></div>
                </div>
            </div>

            <!-- Sprint Breakdown Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3" style="gap: var(--spacing-lg);">
                <div style="background-color: var(--color-gray-50); border-radius: var(--radius-lg); padding: var(--spacing-lg); border: 1px solid var(--color-gray-200); display: flex; align-items: center; justify-between;">
                    <div>
                        <div class="text-muted" style="font-size: var(--font-size-xs); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">To Do</div>
                        <div style="margin-top: var(--spacing-xs); font-size: var(--font-size-3xl); font-weight: 700; color: var(--color-gray-900);" x-text="boardMetrics.sprint.status_breakdown['To Do'] || 0"></div>
                    </div>
                    <div style="height: 2.5rem; width: 2.5rem; border-radius: var(--radius-full); background-color: var(--color-gray-200); display: flex; align-items: center; justify-content: center; color: var(--color-gray-600);">
                        <svg style="height: 1.5rem; width: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
                <div style="background-color: rgba(59, 130, 246, 0.1); border-radius: var(--radius-lg); padding: var(--spacing-lg); border: 1px solid rgba(59, 130, 246, 0.2); display: flex; align-items: center; justify-between;">
                    <div>
                        <div style="font-size: var(--font-size-xs); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-info);">In Progress</div>
                        <div style="margin-top: var(--spacing-xs); font-size: var(--font-size-3xl); font-weight: 700; color: #1E40AF;" x-text="boardMetrics.sprint.status_breakdown['In Progress'] || 0"></div>
                    </div>
                    <div style="height: 2.5rem; width: 2.5rem; border-radius: var(--radius-full); background-color: rgba(59, 130, 246, 0.2); display: flex; align-items: center; justify-content: center; color: #1E40AF;">
                        <svg style="height: 1.5rem; width: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                </div>
                <div style="background-color: var(--color-success-light); border-radius: var(--radius-lg); padding: var(--spacing-lg); border: 1px solid rgba(16, 185, 129, 0.2); display: flex; align-items: center; justify-content: center;">
                    <div>
                        <div class="text-success" style="font-size: var(--font-size-xs); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">Done</div>
                        <div style="margin-top: var(--spacing-xs); font-size: var(--font-size-3xl); font-weight: 700; color: #065F46;" x-text="boardMetrics.sprint.status_breakdown['Done'] || 0"></div>
                    </div>
                    <div style="height: 2.5rem; width: 2.5rem; border-radius: var(--radius-full); background-color: rgba(16, 185, 129, 0.2); display: flex; align-items: center; justify-content: center; color: #065F46;">
                        <svg style="height: 1.5rem; width: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1: Service Metrics -->
        <div class="grid grid-cols-1 lg:grid-cols-2" style="gap: var(--spacing-xl);">
            <div class="card" style="padding: var(--spacing-lg);">
                <h3 class="text-primary" style="font-size: var(--font-size-lg); font-weight: 700; margin-bottom: var(--spacing-lg);">Service Ticket Volume (30 Days)</h3>
                <div style="position: relative; height: 20rem;">
                    <canvas id="serviceVolumeChart"></canvas>
                </div>
            </div>

            <div class="card" style="padding: var(--spacing-lg);">
                <h3 class="text-primary" style="font-size: var(--font-size-lg); font-weight: 700; margin-bottom: var(--spacing-lg);">Issue Type Distribution</h3>
                <div style="position: relative; height: 20rem; display: flex; justify-content: center;">
                    <canvas id="serviceTypesChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Charts Row 2: Demand Metrics -->
        <div class="grid grid-cols-1 lg:grid-cols-2" style="gap: var(--spacing-xl);">
            <div class="card" style="padding: var(--spacing-lg);">
                <h3 class="text-primary" style="font-size: var(--font-size-lg); font-weight: 700; margin-bottom: var(--spacing-lg);">Demand Throughput (Weekly)</h3>
                <div style="position: relative; height: 20rem;">
                    <canvas id="demandThroughputChart"></canvas>
                </div>
            </div>

            <div class="card" style="padding: var(--spacing-lg);">
                <h3 class="text-primary" style="font-size: var(--font-size-lg); font-weight: 700; margin-bottom: var(--spacing-lg);">Cycle Time Distribution</h3>
                <div style="position: relative; height: 20rem;">
                    <canvas id="demandCycleTimeChart"></canvas>
                </div>
            </div>
        </div>

        <!-- AI-Powered Predictions -->
        <div class="card" style="padding: var(--spacing-xl); background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;" x-show="predictions">
            <div class="flex items-center mb-6" style="gap: var(--spacing-md);">
                <svg style="height: 2rem; width: 2rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                <div>
                    <h2 style="font-size: var(--font-size-2xl); font-weight: 700;">AI-Powered Predictions</h2>
                    <p style="opacity: 0.9; font-size: var(--font-size-sm); margin-top: var(--spacing-xs);">Machine learning insights for proactive management</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3" style="gap: var(--spacing-lg);">
                <!-- Volume Prediction -->
                <div style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); padding: var(--spacing-lg); border-radius: var(--radius-lg); border: 1px solid rgba(255,255,255,0.2);">
                    <div style="font-size: var(--font-size-xs); opacity: 0.8; margin-bottom: var(--spacing-sm);">NEXT MONTH VOLUME</div>
                    <div style="font-size: var(--font-size-4xl); font-weight: 700; margin-bottom: var(--spacing-sm);" x-text="predictions.volume_prediction?.predicted_volume || 0"></div>
                    <div class="flex items-center" style="gap: var(--spacing-xs);">
                        <span style="font-size: var(--font-size-xs);">Confidence:</span>
                        <span style="font-size: var(--font-size-sm); font-weight: 600;" x-text="(predictions.volume_prediction?.confidence || 0) + '%'"></span>
                    </div>
                    <div style="margin-top: var(--spacing-sm); font-size: var(--font-size-xs); opacity: 0.9;">
                        Trend: <span x-text="predictions.volume_prediction?.trend || 'stable'" style="text-transform: capitalize;"></span>
                    </div>
                </div>

                <!-- SLA Risk -->
                <div style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); padding: var(--spacing-lg); border-radius: var(--radius-lg); border: 1px solid rgba(255,255,255,0.2);">
                    <div style="font-size: var(--font-size-xs); opacity: 0.8; margin-bottom: var(--spacing-sm);">SLA BREACH RISK</div>
                    <div style="font-size: var(--font-size-4xl); font-weight: 700; margin-bottom: var(--spacing-sm); text-transform: uppercase;" x-text="predictions.sla_risk?.risk_level || 'low'"></div>
                    <div style="font-size: var(--font-size-sm); margin-bottom: var(--spacing-sm);" x-text="(predictions.sla_risk?.risk_percentage || 0) + '% current breach rate'"></div>
                    <div style="font-size: var(--font-size-xs); opacity: 0.9;" x-text="predictions.sla_risk?.recommendation || ''"></div>
                </div>

                <!-- Capacity Status -->
                <div style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); padding: var(--spacing-lg); border-radius: var(--radius-lg); border: 1px solid rgba(255,255,255,0.2);">
                    <div style="font-size: var(--font-size-xs); opacity: 0.8; margin-bottom: var(--spacing-sm);">TEAM CAPACITY</div>
                    <div style="font-size: var(--font-size-4xl); font-weight: 700; margin-bottom: var(--spacing-sm); text-transform: uppercase;" x-text="predictions.capacity?.status || 'healthy'"></div>
                    <div style="font-size: var(--font-size-sm); margin-bottom: var(--spacing-sm);" x-text="'Avg Lead Time: ' + (predictions.capacity?.avg_lead_time || 0) + ' days'"></div>
                    <div style="font-size: var(--font-size-xs); opacity: 0.9;" x-text="predictions.capacity?.recommendation || ''"></div>
                </div>
            </div>

            <!-- Recommendations -->
            <div style="margin-top: var(--spacing-xl);" x-show="predictions.recommendations && predictions.recommendations.length > 0">
                <h3 style="font-size: var(--font-size-lg); font-weight: 600; margin-bottom: var(--spacing-md);">🎯 AI Recommendations</h3>
                <div style="display: flex; flex-direction: column; gap: var(--spacing-md);">
                    <template x-for="rec in predictions.recommendations" :key="rec.title">
                        <div style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); padding: var(--spacing-md); border-radius: var(--radius-md); border-left: 4px solid white;">
                            <div class="flex justify-between items-start">
                                <div style="flex: 1;">
                                    <div class="flex items-center" style="gap: var(--spacing-sm); margin-bottom: var(--spacing-xs);">
                                        <span style="font-size: var(--font-size-sm); font-weight: 700;" x-text="rec.title"></span>
                                        <span style="background: rgba(255,255,255,0.3); padding: 2px 8px; border-radius: var(--radius-full); font-size: var(--font-size-xs); text-transform: uppercase;" x-text="rec.priority"></span>
                                    </div>
                                    <p style="font-size: var(--font-size-sm); opacity: 0.9; margin-bottom: var(--spacing-xs);" x-text="rec.action"></p>
                                    <p style="font-size: var(--font-size-xs); opacity: 0.7;">Expected Impact: <span x-text="rec.expected_impact"></span></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Team Leaderboard -->
        <div class="card" style="padding: var(--spacing-xl);" x-show="leaderboard">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center" style="gap: var(--spacing-md);">
                    <svg style="height: 2rem; width: 2rem; color: var(--color-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                    <div>
                        <h2 class="text-primary" style="font-size: var(--font-size-2xl); font-weight: 700;">Team Leaderboard</h2>
                        <p class="text-muted" style="font-size: var(--font-size-sm); margin-top: var(--spacing-xs);">Top performers this month</p>
                    </div>
                </div>
                <span class="badge-success" x-text="(leaderboard.leaderboard?.total_members || 0) + ' team members'"></span>
            </div>

            <!-- Achievements -->
            <div style="margin-bottom: var(--spacing-xl);" x-show="leaderboard.achievements && leaderboard.achievements.length > 0">
                <h3 style="font-size: var(--font-size-lg); font-weight: 600; margin-bottom: var(--spacing-md);">🏆 Team Achievements</h3>
                <div class="flex flex-wrap" style="gap: var(--spacing-md);">
                    <template x-for="achievement in leaderboard.achievements" :key="achievement.title">
                        <div style="background: linear-gradient(135deg, var(--color-success-light), var(--color-success)); padding: var(--spacing-md); border-radius: var(--radius-lg); flex: 1; min-width: 15rem;">
                            <div style="font-size: var(--font-size-lg); margin-bottom: var(--spacing-xs);" x-text="achievement.title"></div>
                            <div style="font-size: var(--font-size-sm); color: var(--color-gray-700);" x-text="achievement.description"></div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Leaderboard Table -->
            <div style="overflow-x: auto;">
                <table style="min-width: 100%; border-collapse: separate; border-spacing: 0;">
                    <thead style="background-color: var(--color-gray-50);">
                        <tr>
                            <th style="padding: var(--spacing-md); text-align: left; font-size: var(--font-size-xs); font-weight: 700; color: var(--color-gray-500); text-transform: uppercase;">Rank</th>
                            <th style="padding: var(--spacing-md); text-align: left; font-size: var(--font-size-xs); font-weight: 700; color: var(--color-gray-500); text-transform: uppercase;">Team Member</th>
                            <th style="padding: var(--spacing-md); text-align: center; font-size: var(--font-size-xs); font-weight: 700; color: var(--color-gray-500); text-transform: uppercase;">Points</th>
                            <th style="padding: var(--spacing-md); text-align: center; font-size: var(--font-size-xs); font-weight: 700; color: var(--color-gray-500); text-transform: uppercase;">Tickets</th>
                            <th style="padding: var(--spacing-md); text-align: center; font-size: var(--font-size-xs); font-weight: 700; color: var(--color-gray-500); text-transform: uppercase;">Avg Time (hrs)</th>
                            <th style="padding: var(--spacing-md); text-align: left; font-size: var(--font-size-xs); font-weight: 700; color: var(--color-gray-500); text-transform: uppercase;">Badges</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="member in leaderboard.leaderboard?.leaderboard" :key="member.name">
                            <tr style="border-top: 1px solid var(--color-gray-200);" :style="member.rank <= 3 ? 'background-color: var(--color-warning-light);' : ''">
                                <td style="padding: var(--spacing-md);">
                                    <div class="flex items-center">
                                        <span x-show="member.rank === 1" style="font-size: var(--font-size-2xl);">🥇</span>
                                        <span x-show="member.rank === 2" style="font-size: var(--font-size-2xl);">🥈</span>
                                        <span x-show="member.rank === 3" style="font-size: var(--font-size-2xl);">🥉</span>
                                        <span x-show="member.rank > 3" style="font-size: var(--font-size-lg); font-weight: 600; color: var(--color-gray-600);" x-text="member.rank"></span>
                                    </div>
                                </td>
                                <td style="padding: var(--spacing-md);">
                                    <span style="font-weight: 600; font-size: var(--font-size-sm);" x-text="member.name"></span>
                                </td>
                                <td style="padding: var(--spacing-md); text-align: center;">
                                    <span class="badge-success" x-text="member.total_points"></span>
                                </td>
                                <td style="padding: var(--spacing-md); text-align: center; font-size: var(--font-size-sm);" x-text="member.tickets_resolved"></td>
                                <td style="padding: var(--spacing-md); text-align: center; font-size: var(--font-size-sm);" x-text="member.avg_resolution_time"></td>
                                <td style="padding: var(--spacing-md);">
                                    <div class="flex flex-wrap" style="gap: var(--spacing-xs);">
                                        <template x-for="badge in member.badges" :key="badge">
                                            <span style="font-size: var(--font-size-xs);" x-text="badge"></span>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Individual Performance Report -->
        <div class="card" style="overflow: hidden;">
            <div style="padding: var(--spacing-lg); border-bottom: 1px solid var(--color-gray-200); background-color: var(--color-gray-50); display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 class="text-primary" style="font-size: var(--font-size-xl); font-weight: 700;">Individual Performance Report</h3>
                    <p class="text-muted" style="font-size: var(--font-size-sm); margin-top: var(--spacing-xs);">Detailed breakdown by team member</p>
                </div>
                <div class="flex items-center" style="gap: var(--spacing-sm);">
                    <span class="text-muted" style="font-size: var(--font-size-sm);">Sort by:</span>
                    <select x-model="heatmapMetric" style="border: 1px solid var(--color-gray-300); border-radius: var(--radius-md); box-shadow: var(--shadow-sm); font-size: var(--font-size-sm); padding: var(--spacing-sm) var(--spacing-md);">
                        <option value="service_ticket_count">Service Load</option>
                        <option value="demand_ticket_count">Demand Load</option>
                        <option value="avg_resolution_hours">Avg Resolution (Hrs)</option>
                    </select>
                </div>
            </div>
            
            <div style="overflow-x: auto;">
                <table style="min-width: 100%; border-collapse: separate; border-spacing: 0;">
                    <thead style="background-color: var(--color-gray-50);">
                        <tr>
                            <th style="padding: var(--spacing-md) var(--spacing-lg); text-align: left; font-size: var(--font-size-xs); font-weight: 700; color: var(--color-gray-500); text-transform: uppercase; letter-spacing: 0.05em;">Team Member</th>
                            <th style="padding: var(--spacing-md) var(--spacing-lg); text-align: left; font-size: var(--font-size-xs); font-weight: 700; color: var(--color-gray-500); text-transform: uppercase; letter-spacing: 0.05em;">Department</th>
                            <th style="padding: var(--spacing-md) var(--spacing-lg); text-align: center; font-size: var(--font-size-xs); font-weight: 700; color: var(--color-gray-500); text-transform: uppercase; letter-spacing: 0.05em;">Service Tickets</th>
                            <th style="padding: var(--spacing-md) var(--spacing-lg); text-align: center; font-size: var(--font-size-xs); font-weight: 700; color: var(--color-gray-500); text-transform: uppercase; letter-spacing: 0.05em;">Demand Tickets</th>
                            <th style="padding: var(--spacing-md) var(--spacing-lg); text-align: center; font-size: var(--font-size-xs); font-weight: 700; color: var(--color-gray-500); text-transform: uppercase; letter-spacing: 0.05em;">Total Workload</th>
                            <th style="padding: var(--spacing-md) var(--spacing-lg); text-align: center; font-size: var(--font-size-xs); font-weight: 700; color: var(--color-gray-500); text-transform: uppercase; letter-spacing: 0.05em;">Avg Resolution</th>
                            <th style="padding: var(--spacing-md) var(--spacing-lg); text-align: center; font-size: var(--font-size-xs); font-weight: 700; color: var(--color-gray-500); text-transform: uppercase; letter-spacing: 0.05em;">Status</th>
                        </tr>
                    </thead>
                    <tbody style="background-color: var(--color-white);">
                        <template x-for="item in heatmapData" :key="item.assignee">
                            <tr style="border-top: 1px solid var(--color-gray-200); transition: background-color 0.15s;">
                                <td style="padding: var(--spacing-md) var(--spacing-lg); white-space: nowrap;">
                                    <div class="flex items-center">
                                        <div style="flex-shrink: 0; height: 2.5rem; width: 2.5rem; border-radius: var(--radius-full); background-color: var(--color-primary); display: flex; align-items: center; justify-content: center; color: var(--color-white); font-weight: 700; font-size: var(--font-size-sm); box-shadow: var(--shadow-md);">
                                            <span x-text="item.assignee.charAt(0).toUpperCase()"></span>
                                        </div>
                                        <div style="margin-left: var(--spacing-md);">
                                            <div style="font-size: var(--font-size-sm); font-weight: 700; color: var(--color-gray-900);" x-text="item.assignee"></div>
                                            <div class="text-muted" style="font-size: var(--font-size-xs);" x-text="item.email || 'No email'"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-muted" style="padding: var(--spacing-md) var(--spacing-lg); white-space: nowrap; font-size: var(--font-size-sm);" x-text="item.department || 'IT'"></td>
                                <td style="padding: var(--spacing-md) var(--spacing-lg); white-space: nowrap; text-align: center;">
                                    <span class="badge-success" x-text="item.service_ticket_count"></span>
                                </td>
                                <td style="padding: var(--spacing-md) var(--spacing-lg); white-space: nowrap; text-align: center;">
                                    <span style="background-color: rgba(147, 51, 234, 0.1); color: #9333EA; padding: var(--spacing-xs) var(--spacing-md); border-radius: var(--radius-full); font-size: var(--font-size-xs); font-weight: 600;" x-text="item.demand_ticket_count"></span>
                                </td>
                                <td style="padding: var(--spacing-md) var(--spacing-lg); white-space: nowrap; text-align: center; font-size: var(--font-size-sm); font-weight: 700; color: var(--color-gray-900);" x-text="item.service_ticket_count + item.demand_ticket_count"></td>
                                <td class="text-muted" style="padding: var(--spacing-md) var(--spacing-lg); white-space: nowrap; text-align: center; font-size: var(--font-size-sm);" x-text="item.avg_resolution_hours + ' hrs'"></td>
                                <td style="padding: var(--spacing-md) var(--spacing-lg); white-space: nowrap; text-align: center;">
                                    <span :class="item.sla_breach_rate_percent > 0 ? 'badge-danger' : 'badge-success'" x-text="item.sla_breach_rate_percent > 0 ? 'Needs Attention' : 'On Track'"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function dashboard() {
    return {
        stats: {
            service: { total_tickets: 0, sla_breaches: 0, breach_details: [] },
            demand: { total: 0, avg_lead_time: 0 }
        },
        forecast: {},
        riskLevel: 'Low',
        heatmapData: [],
        heatmapMetric: 'service_ticket_count',
        charts: {}, 
        boardMetrics: null,
        isLoading: false,
        executiveSummary: null,
        dateRange: 30,
        showBreachModal: false,
        autoRefreshInterval: null,
        lastRefreshTime: null,
        trends: {
            service: 0,
            demand: 0,
            sla: 0
        },
        insights: [],
        predictions: null,
        leaderboard: null,

        async initDashboard() {
            this.isLoading = true;
            await Promise.all([
                this.fetchExecutive(),
                this.fetchStats(),
                this.fetchTrend(),
                this.fetchHeatmap(),
                this.fetchChartsData(),
                this.fetchPredictions(),
                this.fetchLeaderboard()
            ]);
            this.isLoading = false;
            this.lastRefreshTime = new Date();
            this.startAutoRefresh();
            this.generateInsights();
            this.checkForAlerts();
        },

        startAutoRefresh() {
            // Auto-refresh every 5 minutes
            this.autoRefreshInterval = setInterval(() => {
                this.refreshData();
            }, 300000); // 5 minutes
        },

        stopAutoRefresh() {
            if (this.autoRefreshInterval) {
                clearInterval(this.autoRefreshInterval);
            }
        },

        setDateRange(days) {
            this.dateRange = days;
            this.fetchChartsData();
            this.fetchStats();
        },

        getDateRangeText() {
            const end = new Date();
            const start = new Date();
            start.setDate(start.getDate() - this.dateRange);
            return `${start.toLocaleDateString('tr-TR')} - ${end.toLocaleDateString('tr-TR')}`;
        },

        getTimeSinceRefresh() {
            if (!this.lastRefreshTime) return '';
            const seconds = Math.floor((new Date() - this.lastRefreshTime) / 1000);
            if (seconds < 60) return `${seconds}s ago`;
            const minutes = Math.floor(seconds / 60);
            if (minutes < 60) return `${minutes}m ago`;
            const hours = Math.floor(minutes / 60);
            return `${hours}h ago`;
        },

        generateInsights() {
            this.insights = [];
            
            // SLA Compliance Insight
            if (this.executiveSummary && this.executiveSummary.summary.sla_compliance_rate < 93) {
                this.insights.push({
                    type: 'critical',
                    icon: 'alert',
                    title: 'SLA Compliance Below Target',
                    message: `Current SLA compliance is ${this.executiveSummary.summary.sla_compliance_rate}%. Immediate action required to reach 93% minimum target.`,
                    action: 'Review breached tickets'
                });
            } else if (this.executiveSummary && this.executiveSummary.summary.sla_compliance_rate < 95) {
                this.insights.push({
                    type: 'warning',
                    icon: 'warning',
                    title: 'SLA Compliance Needs Attention',
                    message: `SLA compliance is ${this.executiveSummary.summary.sla_compliance_rate}%. Close to target but not optimal.`,
                    action: 'Monitor closely'
                });
            }

            // High Workload Insight
            if (this.executiveSummary && this.executiveSummary.summary.total_active_tickets > 100) {
                this.insights.push({
                    type: 'info',
                    icon: 'info',
                    title: 'High Ticket Volume',
                    message: `${this.executiveSummary.summary.total_active_tickets} active tickets. Consider resource allocation.`,
                    action: 'Review team capacity'
                });
            }

            // Team Performance Insight
            if (this.executiveSummary && this.executiveSummary.summary.team_performance_score >= 80) {
                this.insights.push({
                    type: 'success',
                    icon: 'check',
                    title: 'Excellent Team Performance',
                    message: `Team performance score is ${this.executiveSummary.summary.team_performance_score}. Keep up the great work!`,
                    action: 'Maintain standards'
                });
            }

            // Lead Time Insight
            if (this.stats.demand.avg_lead_time > 14) {
                this.insights.push({
                    type: 'warning',
                    icon: 'clock',
                    title: 'High Average Lead Time',
                    message: `Average lead time is ${this.stats.demand.avg_lead_time} days. Consider process optimization.`,
                    action: 'Analyze bottlenecks'
                });
            }
        },

        async fetchExecutive() {
            const response = await fetch('/api/metrics/executive');
            this.executiveSummary = await response.json();
        },

        async fetchPredictions() {
            const response = await fetch('/api/metrics/predictions');
            this.predictions = await response.json();
        },

        async fetchLeaderboard() {
            const response = await fetch(`/api/metrics/leaderboard?days=${this.dateRange}`);
            this.leaderboard = await response.json();
        },

        async refreshData() {
            this.isLoading = true;
            await fetch('/api/metrics/refresh', { 
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            await this.initDashboard();
            this.lastRefreshTime = new Date();
            this.generateInsights();
        },

        async fetchStats() {
            const response = await fetch('/api/metrics/stats');
            this.stats = await response.json();
        },

        async fetchTrend() {
            const response = await fetch('/api/metrics/trend');
            const data = await response.json();
            this.forecast = data.forecast;
            this.calculateRisk(data.forecast);
        },

        async fetchHeatmap() {
            const response = await fetch('/api/metrics/heatmap');
            this.heatmapData = await response.json();
        },

        async fetchChartsData() {
            const response = await fetch(`/api/metrics/charts?days=${this.dateRange}`);
            const data = await response.json();
            
            this.boardMetrics = data.board_metrics;

            this.renderServiceVolumeChart(data.service_volume);
            this.renderServiceTypesChart(data.service_types);
            this.renderDemandThroughputChart(data.demand_throughput);
            this.renderDemandCycleTimeChart(data.demand_cycle_time);
        },

        calculateRisk(forecast) {
            if (forecast.sla_breach_forecast > 10) this.riskLevel = 'High';
            else if (forecast.sla_breach_forecast > 5) this.riskLevel = 'Medium';
            else this.riskLevel = 'Low';
        },

        renderServiceVolumeChart(data) {
            this.destroyChart('serviceVolumeChart');
            const ctx = document.getElementById('serviceVolumeChart').getContext('2d');
            this.charts.serviceVolumeChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [
                        { label: 'Created', data: data.created, backgroundColor: '#002D72' },
                        { label: 'Resolved', data: data.resolved, backgroundColor: '#10B981' }
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false, scales: { x: { stacked: false }, y: { beginAtZero: true } } }
            });
        },

        renderServiceTypesChart(data) {
            this.destroyChart('serviceTypesChart');
            const ctx = document.getElementById('serviceTypesChart').getContext('2d');
            this.charts.serviceTypesChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.labels,
                    datasets: [{
                        data: data.data,
                        backgroundColor: ['#002D72', '#10B981', '#F59E0B', '#EF4444', '#6366F1']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        },

        renderDemandThroughputChart(data) {
            this.destroyChart('demandThroughputChart');
            const ctx = document.getElementById('demandThroughputChart').getContext('2d');
            this.charts.demandThroughputChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: Object.keys(data),
                    datasets: [{
                        label: 'Completed Items',
                        data: Object.values(data),
                        borderColor: '#002D72',
                        backgroundColor: 'rgba(0, 45, 114, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
            });
        },

        renderDemandCycleTimeChart(data) {
            this.destroyChart('demandCycleTimeChart');
            const ctx = document.getElementById('demandCycleTimeChart').getContext('2d');
            this.charts.demandCycleTimeChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Issues Count',
                        data: data.data,
                        backgroundColor: '#F59E0B'
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
            });
        },

        exportToPDF() {
            // Hide interactive elements before print
            this.showBreachModal = false;
            
            // Use browser's print dialog (user can save as PDF)
            window.print();
        },

        showNotification(message, type = 'info') {
            const container = document.getElementById('notification-container');
            const notification = document.createElement('div');
            
            const colors = {
                success: 'var(--color-success)',
                error: 'var(--color-danger)',
                warning: 'var(--color-warning)',
                info: 'var(--color-info)'
            };
            
            notification.style.cssText = `
                background: white;
                border-left: 4px solid ${colors[type]};
                padding: var(--spacing-md);
                border-radius: var(--radius-md);
                box-shadow: var(--shadow-lg);
                animation: slideIn 0.3s ease-out;
                display: flex;
                align-items: center;
                gap: var(--spacing-sm);
            `;
            
            notification.innerHTML = `
                <div style="flex: 1; font-size: var(--font-size-sm);">${message}</div>
                <button onclick="this.parentElement.remove()" style="color: var(--color-gray-400); hover:color: var(--color-gray-600);">
                    <svg style="height: 1rem; width: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            `;
            
            container.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        },

        checkForAlerts() {
            // Check SLA breaches
            if (this.stats.service.sla_breaches > 0) {
                this.showNotification(`⚠️ ${this.stats.service.sla_breaches} SLA breach(es) detected!`, 'warning');
            }
            
            // Check predictions
            if (this.predictions && this.predictions.sla_risk.risk_level === 'critical') {
                this.showNotification('🚨 Critical SLA risk detected! Immediate action required.', 'error');
            }
            
            // Check capacity
            if (this.predictions && this.predictions.capacity.status === 'critical') {
                this.showNotification('⚡ Team capacity critical! Consider resource allocation.', 'warning');
            }
        },

        destroyChart(id) {
            if (this.charts[id]) {
                this.charts[id].destroy();
            }
        }
    }
}
</script>
@endsection
