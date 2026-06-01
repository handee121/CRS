<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;


class DashboardController extends Controller
{
    public function index()
    {
        $totalRooms = DB::table('rooms')->count();

        $totalUsers = DB::table('users')->count();

        $totalReservations = DB::table('reservations')->count();

        $cancelledReservations = DB::table('reservations')
            ->where('status', 'CANCELLED')
            ->count();

        
        $logs = DB::table('activitylog_view')
            ->orderBy('timestamp', 'desc')
            ->limit(5)
            ->get();

        return view('admin.admin', compact(
            'totalRooms',
            'totalUsers',
            'totalReservations',
            'cancelledReservations',
            'logs'
        ));
    }

    public function recentToday()
    {
        $logs = DB::table('activity_logs')
            ->join('users', 'activity_logs.user_id', '=', 'users.user_id')
            ->select(
                'activity_logs.log_id',
                'users.first_name',
                'users.last_name',
                'activity_logs.action',
                'activity_logs.timestamp'
            )
            ->whereDate('activity_logs.timestamp', now()) // TODAY ONLY
            ->orderBy('activity_logs.timestamp', 'desc')
            ->limit(10)
            ->get();

        return $logs;
    }
}