```
@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <h2 class="text-2xl font-bold mb-6 text-gray-800">Application Settings</h2>

            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Success!</strong>
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Error!</strong>
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <form action="{{ route('settings.update') }}" method="POST">
                @csrf

                <!-- Jira Settings -->
                <div class="mb-10">
                    <h3 class="text-xl font-semibold text-gray-800 mb-1 border-b pb-2">
                        <span class="text-brand-primary">1.</span> Jira Configuration
                    </h3>
                    <p class="text-sm text-gray-500 mb-6">Configure access to your Jira instance to fetch tickets and metrics.</p>
                    
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <!-- Base URL -->
                        <div class="sm:col-span-4">
                            <label for="jira_base_url" class="block text-sm font-medium text-gray-700">Jira Base URL</label>
                            <div class="mt-1">
                                <input type="url" name="jira_base_url" id="jira_base_url" 
                                    value="{{ $settings['jira_base_url'] ?? config('services.jira.base_url') }}"
                                    placeholder="https://app.suticket.sabanciuniv.edu"
                                    class="shadow-sm focus:ring-brand-primary focus:border-brand-primary block w-full sm:text-sm border-gray-300 rounded-md p-2.5 border">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">The main URL of your Jira instance. Do not include trailing slash.</p>
                        </div>

                        <!-- Email -->
                        <div class="sm:col-span-4">
                            <label for="jira_email" class="block text-sm font-medium text-gray-700">Jira User Email</label>
                            <div class="mt-1">
                                <input type="email" name="jira_email" id="jira_email" 
                                    value="{{ $settings['jira_email'] ?? config('services.jira.email') }}"
                                    placeholder="name@sabanciuniv.edu"
                                    class="shadow-sm focus:ring-brand-primary focus:border-brand-primary block w-full sm:text-sm border-gray-300 rounded-md p-2.5 border">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">The email address you use to log in to Jira.</p>
                        </div>

                        <!-- API Token -->
                        <div class="sm:col-span-4">
                            <label for="jira_api_token" class="block text-sm font-medium text-gray-700">API Token / Personal Access Token (PAT)</label>
                            <div class="mt-1">
                                <input type="password" name="jira_api_token" id="jira_api_token" 
                                    value="{{ \App\Models\Setting::get('jira_api_token') }}"
                                    class="shadow-sm focus:ring-brand-primary focus:border-brand-primary block w-full sm:text-sm border-gray-300 rounded-md p-2.5 border" required>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                For Jira Cloud: Use API Token from <a href="https://id.atlassian.com/manage/api-tokens" target="_blank" class="text-blue-600 hover:underline">Atlassian ID</a>.<br>
                                For Jira Data Center/Server: Use <strong>Personal Access Token (PAT)</strong> from your User Profile.
                            </p>
                        </div>

                        <!-- Service Project -->
                        <div class="sm:col-span-3">
                            <label for="jira_service_project" class="block text-sm font-medium text-gray-700">Service Project Key</label>
                            <div class="mt-1">
                                <input type="text" name="jira_service_project" id="jira_service_project" 
                                    value="{{ $settings['jira_service_project'] ?? 'IT' }}"
                                    class="shadow-sm focus:ring-brand-primary focus:border-brand-primary block w-full sm:text-sm border-gray-300 rounded-md p-2.5 border">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Key for Service Management (e.g., <strong>IT</strong> for IT-SUCourse, IT-SIS).</p>
                        </div>

                        <!-- Demand Project -->
                        <div class="sm:col-span-3">
                            <label for="jira_demand_project" class="block text-sm font-medium text-gray-700">Demand Project Keys</label>
                            <div class="mt-1">
                                <input type="text" name="jira_demand_project" id="jira_demand_project" 
                                    value="{{ $settings['jira_demand_project'] ?? 'BTID, BTACA' }}"
                                    class="shadow-sm focus:ring-brand-primary focus:border-brand-primary block w-full sm:text-sm border-gray-300 rounded-md p-2.5 border">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Comma separated keys for Demand projects (e.g., <strong>BTID, BTACA</strong>).</p>
                        </div>
                    </div>
                </div>

                <!-- Google Sheets Settings -->
                <div class="mb-8">
                    <h3 class="text-xl font-semibold text-gray-800 mb-1 border-b pb-2">
                        <span class="text-brand-primary">2.</span> Google Sheets Configuration
                    </h3>
                    <p class="text-sm text-gray-500 mb-6">Configure where the daily KPI reports will be exported.</p>
                    
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        <!-- JSON Path -->
                        <div class="sm:col-span-6">
                            <label for="google_credentials_path" class="block text-sm font-medium text-gray-700">Service Account JSON Path</label>
                            <div class="mt-1">
                                <input type="text" name="google_credentials_path" id="google_credentials_path" 
                                    value="{{ $settings['google_credentials_path'] ?? config('services.google.credentials_path') }}"
                                    placeholder="/Users/username/project/service-account.json"
                                    class="shadow-sm focus:ring-brand-primary focus:border-brand-primary block w-full sm:text-sm border-gray-300 rounded-md p-2.5 border">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                Absolute path to the Google Service Account JSON file on the server. 
                                <br>Ensure the service account email (inside JSON) has <strong>Editor</strong> access to the sheets below.
                            </p>
                        </div>

                        <!-- Service Sheet ID -->
                        <div class="sm:col-span-6">
                            <label for="google_service_sheet_id" class="block text-sm font-medium text-gray-700">Service KPI Spreadsheet ID</label>
                            <div class="mt-1">
                                <input type="text" name="google_service_sheet_id" id="google_service_sheet_id" 
                                    value="{{ $settings['google_service_sheet_id'] ?? config('services.google.sheets.service_id') }}"
                                    class="shadow-sm focus:ring-brand-primary focus:border-brand-primary block w-full sm:text-sm border-gray-300 rounded-md p-2.5 border">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">ID from the URL: docs.google.com/spreadsheets/d/<strong>THIS_PART</strong>/edit</p>
                        </div>

                        <!-- Demand Sheet ID -->
                        <div class="sm:col-span-6">
                            <label for="google_demand_sheet_id" class="block text-sm font-medium text-gray-700">Demand KPI Spreadsheet ID</label>
                            <div class="mt-1">
                                <input type="text" name="google_demand_sheet_id" id="google_demand_sheet_id" 
                                    value="{{ $settings['google_demand_sheet_id'] ?? config('services.google.sheets.demand_id') }}"
                                    class="shadow-sm focus:ring-brand-primary focus:border-brand-primary block w-full sm:text-sm border-gray-300 rounded-md p-2.5 border">
                            </div>
                             <p class="mt-1 text-xs text-gray-500">ID from the URL: docs.google.com/spreadsheets/d/<strong>THIS_PART</strong>/edit</p>
                        </div>
                    </div>
                </div>

                <div class="pt-5 border-t border-gray-200">
                    <div class="flex justify-end">
                        <button type="submit" class="ml-3 inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-brand-primary hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            Save Configuration
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
