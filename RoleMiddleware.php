<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AvailableRoomController extends Controller
{
    public function index(Request $request)
    {
        $rooms = DB::table('rooms')

            
            ->when($request->room_name, function ($query) use ($request) {
                $query->where('room_name', 'like', '%' . $request->room_name . '%');
            })

            
            ->when($request->location, function ($query) use ($request) {
                $query->where('location', $request->location);
            })

            
            ->when($request->reservation_date && $request->timeslot_id, function ($query) use ($request) {

                $query->whereNotIn('room_id', function ($sub) use ($request) {
                    $sub->select('room_id')
                        ->from('reservations')
                        ->where('reservation_date', $request->reservation_date)
                        ->where('timeslot_id', $request->timeslot_id)
                        ->where('status', 'BOOKED');
                });
            })

            ->get();

        return response()->json($rooms);
    }
}