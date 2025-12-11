<?php

namespace App\Jobs;

use App\Services\Google\GoogleSheetsService;
use App\Services\Jira\DemandMetricsService;
use App\Services\Jira\ServiceMetricsService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ComputeDailyKpisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(
        ServiceMetricsService $serviceMetrics,
        DemandMetricsService $demandMetrics,
        GoogleSheetsService $googleSheets
    ): void {
        Log::info('Starting Daily KPI Computation...');

        $yesterday = Carbon::yesterday(); // Or today(), depending on when job runs. 
        // If running at 23:00, use today(). If running at 01:00, use yesterday().
        // Let's assume we run at 23:00 for "today".
        $date = Carbon::today();

        // 1. Service KPIs
        try {
            $serviceKpis = $serviceMetrics->getDailyServiceKPIs($date);
            $serviceRow = [
                $serviceKpis['date'],
                $serviceKpis['total_tickets'],
                $serviceKpis['resolved_tickets'],
                $serviceKpis['sla_breaches'],
                $serviceKpis['avg_resolution_hours'],
                // Add placeholders for top/worst performer if not implemented yet
                'N/A', 
                'N/A'
            ];
            
            $sheetId = \App\Models\Setting::get('google_service_sheet_id', config('services.google.sheets.service_id'));
            if ($sheetId) {
                $googleSheets->appendRow($sheetId, 'Sheet1!A:G', $serviceRow);
                Log::info('Service KPIs exported.');
            }
        } catch (\Exception $e) {
            Log::error('Failed to export Service KPIs: ' . $e->getMessage());
        }

        // 2. Demand KPIs
        try {
            $demandKpis = $demandMetrics->getDailyDemandKPIs($date);
            $demandRow = [
                $demandKpis['date'],
                $demandKpis['total_demands'],
                $demandKpis['completed_demands'],
                $demandKpis['avg_lead_time_days'],
                $demandKpis['avg_cycle_time_days'],
                $demandKpis['backlog_count'],
                $demandKpis['oldest_backlog_age_days']
            ];

            $demandSheetId = \App\Models\Setting::get('google_demand_sheet_id', config('services.google.sheets.demand_id'));
            if ($demandSheetId) {
                $googleSheets->appendRow($demandSheetId, 'Sheet1!A:G', $demandRow);
                Log::info('Demand KPIs exported.');
            }
        } catch (\Exception $e) {
            Log::error('Failed to export Demand KPIs: ' . $e->getMessage());
        }
        
        Log::info('Daily KPI Computation Finished.');
    }
}
