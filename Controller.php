<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('reservations')
            ->join('users', 'reservations.user_id', '=', 'users.user_id')
            ->join('rooms', 'reservations.room_id', '=', 'rooms.room_id')
            ->join('timeslots', 'reservations.timeslot_id', '=', 'timeslots.timeslot_id')
            ->select(
                'rooms.room_name',
                'users.first_name',
                'users.last_name',
                'reservations.reservation_date',
                'timeslots.start_time',
                'timeslots.end_time',
                'reservations.status'
            )

           
            ->where('reservations.status', 'Booked');

     
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('rooms.room_name', 'like', '%' . $request->search . '%')
                  ->orWhere('users.first_name', 'like', '%' . $request->search . '%')
                  ->orWhere('users.last_name', 'like', '%' . $request->search . '%');
            });
        }

      
        if ($request->sort == 'oldest') {
            $query->orderBy('reservations.reservation_date', 'asc');
        } else {
            // default = newest
            $query->orderBy('reservations.reservation_date', 'desc');
        }

        $schedule = $query->paginate(10)->withQueryString();

        return view('admin.schedule', compact('schedule'));
    }
}