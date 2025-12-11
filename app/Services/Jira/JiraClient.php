<?php

namespace App\Services\Jira;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Setting;

class JiraClient
{
    protected string $baseUrl;
    protected string $email;
    protected string $apiToken;

    public function __construct()
    {
        $this->baseUrl = rtrim(Setting::get('jira_base_url'), '/');
        $this->email = trim(Setting::get('jira_email'));
        $this->apiToken = trim(Setting::get('jira_api_token'));
    }

    /**
     * Search issues using JQL
     *
     * @param string $jql
     * @param array $fields
     * @param int $maxResults
     * @return array
     */
    public function searchIssues(string $jql, array $fields = ['*all'], int $maxResults = 1000): array
    {
        // Note: For production, we might need pagination handling if results > maxResults
        // But for now we stick to the requested signature.
        
        // Debug logging
        Log::info("Jira Search Request", [
            'url' => "{$this->baseUrl}/rest/api/3/search",
            'jql' => $jql
        ]);

        // Jira Cloud requires /rest/api/3/search/jql for strict JQL searches via POST
        // Body structure: { "jql": "...", "fields": [...], "maxResults": ... }
        $response = Http::withBasicAuth($this->email, $this->apiToken)
            ->acceptJson()
            ->post("{$this->baseUrl}/rest/api/3/search", [ 
                'jql' => $jql,
                'fields' => $fields,
                'maxResults' => $maxResults,
            ]);
            
        // If 410, try the new specific endpoint suggested by error message
        if ($response->status() === 410) {
             $fallbackUrl = "{$this->baseUrl}/rest/api/3/search/jql";
             Log::info("Trying fallback URL: {$fallbackUrl}");
             
             $response = Http::withBasicAuth($this->email, $this->apiToken)
                ->acceptJson()
                ->post($fallbackUrl, [
                    'jql' => $jql,
                    'fields' => $fields,
                    'maxResults' => $maxResults,
                ]);
        }

        if ($response->failed()) {
            Log::error('Jira API Error', [
                'url' => $response->effectiveUri(),
                'status' => $response->status(),
                'body' => $response->body(),
                'jql' => $jql
            ]);
            return [];
        }

        $data = $response->json();
        
        // Debug log for successful response
        Log::info('Jira API Success', [
            'url' => $response->effectiveUri(),
            'issue_count' => count($data['issues'] ?? []),
            'total' => $data['total'] ?? 'unknown'
        ]);

        return $data['issues'] ?? [];
    }

    /**
     * Get a single issue by key
     *
     * @param string $issueKey
     * @return array|null
     */
    /**
     * Get issues from a specific Service Desk Queue
     *
     * @param int $serviceDeskId
     * @param int $queueId
     * @return array
     */
    public function getQueueIssues(int $serviceDeskId, int $queueId): array
    {
        // Endpoint: /rest/servicedeskapi/servicedesk/{serviceDeskId}/queue/{queueId}/issue
        $endpoint = "{$this->baseUrl}/rest/servicedeskapi/servicedesk/{$serviceDeskId}/queue/{$queueId}/issue";
        
        Log::info("Fetching Queue Issues", ['url' => $endpoint]);

        // Use Basic Auth as Bearer failed in diagnostic
        $response = Http::withBasicAuth($this->email, $this->apiToken)
            ->withHeaders([
                'X-ExperimentalApi' => 'opt-in',
                'Accept' => 'application/json',
            ])
            ->acceptJson()
            ->get($endpoint);

        if ($response->failed()) {
            Log::error("Jira Service Desk API Error fetching queue {$queueId}", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return [];
        }

        $data = $response->json();
        return $data['values'] ?? [];
    }

    public function getIssue(string $issueKey): ?array
    {
        $response = Http::withBasicAuth($this->email, $this->apiToken)
            ->acceptJson()
            ->get("{$this->baseUrl}/rest/api/2/issue/{$issueKey}");

        if ($response->failed()) {
            Log::error("Jira API Error fetching issue {$issueKey}", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return null;
        }

        return $response->json();
    }

    /**
     * Generic GET request to any Jira endpoint
     * 
     * @param string $endpoint Relative path (e.g. /rest/agile/1.0/board/331)
     * @return array|null
     */
    public function get(string $endpoint): ?array
    {
        $url = "{$this->baseUrl}/" . ltrim($endpoint, '/');
        
        $response = Http::withBasicAuth($this->email, $this->apiToken)
            ->acceptJson()
            ->get($url);

        if ($response->failed()) {
            Log::error("Jira API Error fetching {$endpoint}", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return null;
        }

        return $response->json();
    }

    /**
     * Test connection to Jira
     * 
     * @return bool
     * @throws \Exception
     */
    public function testConnection(): bool
    {
        // Use /myself endpoint to verify credentials
        // We found that /servicedeskapi/info is public, so it gave false positives.
        $endpoint = "{$this->baseUrl}/rest/api/2/myself";
        
        // Try Basic Auth with Email first
        $response = Http::withBasicAuth($this->email, $this->apiToken)
            ->acceptJson()
            ->get($endpoint);

        if ($response->successful()) {
            return true;
        }

        // Try Basic Auth with Username
        $username = explode('@', $this->email)[0];
        if ($username !== $this->email) {
            $responseUsername = Http::withBasicAuth($username, $this->apiToken)
                ->acceptJson()
                ->get($endpoint);
                
            if ($responseUsername->successful()) {
                Log::warning("Jira Connection: Basic Auth with Username worked.");
                return true;
            }
        }
        
        // Try Bearer Token (PAT)
        $responseBearer = Http::withToken($this->apiToken)
            ->acceptJson()
            ->get($endpoint);
            
        if ($responseBearer->successful()) {
            return true;
        }

        // Construct error message
        $status = $response->status();
        $body = $response->body();
        
        $errorMsg = "Connection failed. Endpoint: {$endpoint}. Status: {$status}. Body: " . Str::limit($body, 100);

        if ($status === 401) {
            $errorMsg .= " Unauthorized. Check credentials.";
        } elseif ($status === 404) {
            $errorMsg .= " Endpoint not found. Check Base URL.";
        }

        throw new \Exception($errorMsg);
    }

    /**
     * Get worklogs for issues (actual time spent)
     */
    public function getIssueWorklogs(string $issueKey): array
    {
        $response = Http::withBasicAuth($this->email, $this->apiToken)
            ->acceptJson()
            ->get("{$this->baseUrl}/rest/api/3/issue/{$issueKey}/worklog");

        if ($response->failed()) {
            Log::error("Failed to fetch worklogs for {$issueKey}");
            return [];
        }

        return $response->json()['worklogs'] ?? [];
    }

    /**
     * Get sprint information
     */
    public function getSprint(int $sprintId): ?array
    {
        $response = Http::withBasicAuth($this->email, $this->apiToken)
            ->acceptJson()
            ->get("{$this->baseUrl}/rest/agile/1.0/sprint/{$sprintId}");

        if ($response->failed()) {
            return null;
        }

        return $response->json();
    }

    /**
     * Get sprint issues with detailed fields
     */
    public function getSprintIssues(int $sprintId, array $fields = ['*all']): array
    {
        $response = Http::withBasicAuth($this->email, $this->apiToken)
            ->acceptJson()
            ->get("{$this->baseUrl}/rest/agile/1.0/sprint/{$sprintId}/issue", [
                'fields' => implode(',', $fields),
                'maxResults' => 1000
            ]);

        if ($response->failed()) {
            return [];
        }

        return $response->json()['issues'] ?? [];
    }

    /**
     * Get issue links (dependencies, blocks, etc.)
     */
    public function getIssueLinks(string $issueKey): array
    {
        $response = Http::withBasicAuth($this->email, $this->apiToken)
            ->acceptJson()
            ->get("{$this->baseUrl}/rest/api/3/issue/{$issueKey}", [
                'fields' => 'issuelinks'
            ]);

        if ($response->failed()) {
            return [];
        }

        return $response->json()['fields']['issuelinks'] ?? [];
    }

    /**
     * Get all components for a project
     */
    public function getProjectComponents(string $projectKey): array
    {
        $response = Http::withBasicAuth($this->email, $this->apiToken)
            ->acceptJson()
            ->get("{$this->baseUrl}/rest/api/3/project/{$projectKey}/components");

        if ($response->failed()) {
            return [];
        }

        return $response->json();
    }

    /**
     * Get velocity report data
     */
    public function getVelocityReport(int $boardId): array
    {
        $response = Http::withBasicAuth($this->email, $this->apiToken)
            ->acceptJson()
            ->get("{$this->baseUrl}/rest/greenhopper/1.0/rapid/charts/velocity", [
                'rapidViewId' => $boardId
            ]);

        if ($response->failed()) {
            Log::warning("Failed to fetch velocity report for board {$boardId}");
            return [];
        }

        return $response->json();
    }

    /**
     * Get burndown chart data
     */
    public function getBurndownChart(int $sprintId): array
    {
        $response = Http::withBasicAuth($this->email, $this->apiToken)
            ->acceptJson()
            ->get("{$this->baseUrl}/rest/greenhopper/1.0/rapid/charts/scopechangeburndownchart", [
                'sprintId' => $sprintId
            ]);

        if ($response->failed()) {
            return [];
        }

        return $response->json();
    }

    /**
     * Get custom field values for issues
     */
    public function getCustomFieldData(string $jql, string $customFieldId): array
    {
        $issues = $this->searchIssues($jql, [$customFieldId, 'key']);
        
        $data = [];
        foreach ($issues as $issue) {
            if (isset($issue['fields'][$customFieldId])) {
                $data[$issue['key']] = $issue['fields'][$customFieldId];
            }
        }

        return $data;
    }

    /**
     * Get issue transitions history
     */
    public function getIssueChangelog(string $issueKey): array
    {
        $response = Http::withBasicAuth($this->email, $this->apiToken)
            ->acceptJson()
            ->get("{$this->baseUrl}/rest/api/3/issue/{$issueKey}", [
                'expand' => 'changelog'
            ]);

        if ($response->failed()) {
            return [];
        }

        return $response->json()['changelog']['histories'] ?? [];
    }

    /**
     * Get issue comments
     */
    public function getIssueComments(string $issueKey): array
    {
        $response = Http::withBasicAuth($this->email, $this->apiToken)
            ->acceptJson()
            ->get("{$this->baseUrl}/rest/api/3/issue/{$issueKey}/comment");

        if ($response->failed()) {
            return [];
        }

        return $response->json()['comments'] ?? [];
    }

    /**
     * Get all priorities
     */
    public function getPriorities(): array
    {
        $response = Http::withBasicAuth($this->email, $this->apiToken)
            ->acceptJson()
            ->get("{$this->baseUrl}/rest/api/3/priority");

        if ($response->failed()) {
            return [];
        }

        return $response->json();
    }
}
