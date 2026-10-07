<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;

class ActivityLogController extends Controller
{
    public function index()
    {
        $activities = ActivityLog::with('user')->latest()->paginate(30);

        return view('activities.index', compact('activities'));
    }
}
