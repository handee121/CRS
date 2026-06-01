<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {

        $query = DB::table('activitylog_view');

 
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->search . '%')
                  ->orWhere('last_name', 'like', '%' . $request->search . '%')
                  ->orWhere('action', 'like', '%' . $request->search . '%');
            });
        }

 
        if ($request->role) {

            if ($request->role == 'admin') {
                $query->where('role_id', 1);
            }

            if ($request->role == 'instructor') {
                $query->where('role_id', 3);
            }
        }

        if ($request->sort == 'newest') {
            $query->orderBy('timestamp', 'desc');
        } elseif ($request->sort == 'oldest') {
            $query->orderBy('timestamp', 'asc');
        } else {
            $query->orderBy('timestamp', 'desc');
        }


        $logs = $query->paginate(10)->withQueryString();

        return view('admin.system_settings', compact('logs'));
    }
}