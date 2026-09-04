<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::query()->with('user');

        if ($search = $request->input('search')) {
            $query->where('description', 'like', "%{$search}%");
        }
        if ($logName = $request->input('log_name')) {
            $query->where('log_name', $logName);
        }
        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        return view('admin.activity_logs.index', [
            'logs' => $query->latest()->paginate(25)->withQueryString(),
            'logNames' => ActivityLog::query()->select('log_name')->distinct()->orderBy('log_name')->pluck('log_name'),
            'search' => $search,
            'filters' => [
                'log_name' => $request->input('log_name'),
                'user_id' => $request->input('user_id'),
            ],
        ]);
    }
}
