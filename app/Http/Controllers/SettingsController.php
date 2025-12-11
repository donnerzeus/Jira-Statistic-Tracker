<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');

        return view('settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->except('_token');

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        // Test Connection
        try {
            $jira = new \App\Services\Jira\JiraClient();
            $jira->testConnection();
            $message = 'Settings saved and Jira connection verified successfully.';
            $status = 'success';
        } catch (\Exception $e) {
            $message = 'Settings saved, BUT Jira connection failed: ' . $e->getMessage();
            $status = 'error';
        }

        return redirect()->route('settings.index')->with($status, $message);
    }
}
