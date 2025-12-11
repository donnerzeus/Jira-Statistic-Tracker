<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ExportService
{
    /**
     * Export data to Excel
     */
    public function exportToExcel(array $data, string $filename = 'export'): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        if (isset($data['headers'])) {
            $col = 'A';
            foreach ($data['headers'] as $header) {
                $sheet->setCellValue($col . '1', $header);
                $sheet->getStyle($col . '1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '002D72']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
                ]);
                $col++;
            }
        }

        // Set data
        if (isset($data['rows'])) {
            $row = 2;
            foreach ($data['rows'] as $dataRow) {
                $col = 'A';
                foreach ($dataRow as $value) {
                    $sheet->setCellValue($col . $row, $value);
                    $col++;
                }
                $row++;
            }
        }

        // Auto-size columns
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Save to temp file
        $filepath = storage_path("app/exports/{$filename}.xlsx");
        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        return $filepath;
    }

    /**
     * Export data to CSV
     */
    public function exportToCSV(array $data, string $filename = 'export'): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        if (isset($data['headers'])) {
            $col = 'A';
            foreach ($data['headers'] as $header) {
                $sheet->setCellValue($col . '1', $header);
                $col++;
            }
        }

        // Set data
        if (isset($data['rows'])) {
            $row = 2;
            foreach ($data['rows'] as $dataRow) {
                $col = 'A';
                foreach ($dataRow as $value) {
                    $sheet->setCellValue($col . $row, $value);
                    $col++;
                }
                $row++;
            }
        }

        // Save to temp file
        $filepath = storage_path("app/exports/{$filename}.csv");
        $writer = new Csv($spreadsheet);
        $writer->save($filepath);

        return $filepath;
    }

    /**
     * Prepare dashboard data for export
     */
    public function prepareDashboardExport(array $stats, array $charts): array
    {
        return [
            'headers' => [
                'Metric',
                'Value',
                'Status',
                'Timestamp'
            ],
            'rows' => [
                ['Service Tickets Today', $stats['service']['total_tickets'] ?? 0, 'Active', now()->format('Y-m-d H:i:s')],
                ['SLA Breaches', $stats['service']['sla_breaches'] ?? 0, 'Warning', now()->format('Y-m-d H:i:s')],
                ['Demand Tickets (30d)', $stats['demand']['total'] ?? 0, 'Active', now()->format('Y-m-d H:i:s')],
                ['Avg Lead Time', $stats['demand']['avg_lead_time'] ?? 0, 'Info', now()->format('Y-m-d H:i:s')],
            ]
        ];
    }

    /**
     * Prepare leaderboard data for export
     */
    public function prepareLeaderboardExport(array $leaderboard): array
    {
        $rows = [];
        
        foreach ($leaderboard['leaderboard'] ?? [] as $member) {
            $rows[] = [
                $member['rank'],
                $member['name'],
                $member['total_points'],
                $member['tickets_resolved'],
                $member['avg_resolution_time'],
                implode(', ', $member['badges'] ?? [])
            ];
        }

        return [
            'headers' => [
                'Rank',
                'Name',
                'Points',
                'Tickets Resolved',
                'Avg Resolution Time (hrs)',
                'Badges'
            ],
            'rows' => $rows
        ];
    }
}
