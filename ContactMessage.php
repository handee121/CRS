<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class IDashboardController extends Controller
{
    public function index()
    {
        // LOGGED IN USER
        $userId = Auth::id();

        if (!$userId) {
            abort(401, 'Unauthorized');
        }


        $activeReservations = DB::table('reservations')
            ->where('user_id', $userId)
            ->where('status', 'BOOKED')
            ->whereDate('reservation_date', '>=', now())
            ->count();


        $totalReservations = DB::table('reservations')
            ->where('user_id', $userId)
            ->count();


        $cancelledTotal = DB::table('reservations')
            ->where('user_id', $userId)
            ->where('status', 'CANCELLED')
            ->count();

        $recentActivities = DB::table('reservations')
            ->join('rooms', 'reservations.room_id', '=', 'rooms.room_id')

            ->where('reservations.user_id', $userId)

            ->select(
                'reservations.reservation_id',
                'rooms.room_name',
                'reservations.reservation_date',
                'reservations.start_time',
                'reservations.end_time',
                'reservations.status',
                'reservations.created_at'
            )

            ->orderBy('reservations.created_at', 'desc')

            ->limit(3)

            ->get();

        return view('instructor.instructor', compact(
            'activeReservations',
            'totalReservations',
            'cancelledTotal',
            'recentActivities'
        ));
    }
}