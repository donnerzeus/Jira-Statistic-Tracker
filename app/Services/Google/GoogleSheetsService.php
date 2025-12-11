<?php

namespace App\Services\Google;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Log;

class GoogleSheetsService
{
    protected Client $client;
    protected ?Sheets $service = null;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setApplicationName(config('app.name'));
        $this->client->setScopes([Sheets::SPREADSHEETS]);
        
        $credentialsPath = \App\Models\Setting::get('google_credentials_path', config('services.google.credentials_path'));
        $this->client->setAuthConfig($credentialsPath);
        $this->client->setAccessType('offline');
    }

    protected function getService(): Sheets
    {
        if (!$this->service) {
            $this->service = new Sheets($this->client);
        }
        return $this->service;
    }

    /**
     * Append a row to a spreadsheet
     *
     * @param string $spreadsheetId
     * @param string $range e.g. 'Sheet1!A1'
     * @param array $values Single row of values
     * @return bool
     */
    public function appendRow(string $spreadsheetId, string $range, array $values): bool
    {
        try {
            $body = new ValueRange([
                'values' => [$values]
            ]);

            $params = [
                'valueInputOption' => 'USER_ENTERED'
            ];

            $this->getService()->spreadsheets_values->append(
                $spreadsheetId,
                $range,
                $body,
                $params
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Google Sheets Append Error: ' . $e->getMessage());
            return false;
        }
    }
}
