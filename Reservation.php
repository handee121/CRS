<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReserveRoomController extends Controller
{

    public function index(Request $request)
    {
        $query = DB::table('room_view');

        // filters 
        if ($request->search) {
            $query->where('room_name', 'like', '%' . $request->search . '%');
        }

        if ($request->location) {
            $query->where('location_name', $request->location);
        }

        if ($request->capacity == 'small') {
            $query->where('capacity', '<=', 30);
        } elseif ($request->capacity == 'medium') {
            $query->whereBetween('capacity', [31, 50]);
        } elseif ($request->capacity == 'large') {
            $query->where('capacity', '>', 50);
        }

        $rooms = $query->get();
        $locations = DB::table('locations')->get();

        return view('instructor.reserve_room', compact(
            'rooms',
            'locations'
        ))->with('selectedRoom', $request->room_id);
    }


    public function store(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,room_id',
            'reservation_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
        ]);

        if (
            strtotime($request->start_time) < strtotime('06:00') ||
            strtotime($request->end_time) > strtotime('20:00')
        ) {

            return back()->withErrors([
                'time' => 'Reservations are only allowed from 6:00 AM to 8:00 PM.'
            ]);
        }

        DB::beginTransaction();

        try {

            $userId = Auth::id();

            $roomConflict = DB::table('reservations')
                ->where('room_id', $request->room_id)
                ->where('reservation_date', $request->reservation_date)
                ->where('status', 'BOOKED')
                ->where(function ($q) use ($request) {

                    $q->whereBetween('start_time', [
                        $request->start_time,
                        $request->end_time
                    ])

                    ->orWhereBetween('end_time', [
                        $request->start_time,
                        $request->end_time
                    ])

                    ->orWhere(function ($q2) use ($request) {

                        $q2->where('start_time', '<=', $request->start_time)
                           ->where('end_time', '>=', $request->end_time);

                    })

                    ->orWhere(function ($q2) use ($request) {

                        $q2->where('start_time', $request->start_time)
                           ->orWhere('end_time', $request->end_time);

                    });

                })
                ->exists();


            $instructorConflict = DB::table('reservations')
                ->where('user_id', $userId)
                ->where('reservation_date', $request->reservation_date)
                ->where('status', 'BOOKED')
                ->where(function ($q) use ($request) {

                    $q->whereBetween('start_time', [
                        $request->start_time,
                        $request->end_time
                    ])

                    ->orWhereBetween('end_time', [
                        $request->start_time,
                        $request->end_time
                    ])

                    ->orWhere(function ($q2) use ($request) {

                        $q2->where('start_time', '<=', $request->start_time)
                           ->where('end_time', '>=', $request->end_time);

                    });

                })
                ->exists();


            if ($roomConflict) {

                DB::rollBack();

                return back()->withErrors([
                    'conflict' => 'Room already booked for this time.'
                ]);
            }


            if ($instructorConflict) {

                DB::rollBack();

                return back()->withErrors([
                    'conflict' => 'You already have a reservation at this time.'
                ]);
            }

            DB::table('reservations')->insert([

                'user_id' => $userId,
                'room_id' => $request->room_id,
                'reservation_date' => $request->reservation_date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'status' => 'BOOKED'

            ]);


            DB::table('activity_logs')->insert([

                'user_id' => Auth::id(),
                'action' => 'Created Reservation | Room ID: ' . $request->room_id,
                'timestamp' => now()

            ]);

            DB::commit();

            return back()->with(
                'success',
                'Reservation successfully created.'
            );

        } catch (\Exception $e) {

            DB::rollBack();

            return back()->withErrors([
                'error' => $e->getMessage()
            ]);
        }
    }


    public function checkConflict(Request $request)
    {
        $conflict = DB::table('reservations')
            ->where('room_id', $request->room_id)
            ->where('reservation_date', $request->reservation_date)
            ->where('status', 'BOOKED')
            ->where(function ($q) use ($request) {

                $q->where('start_time', '<', $request->end_time)
                ->where('end_time', '>', $request->start_time);

            })
            ->exists();

        return response()->json([

            'available' => !$conflict,

            'message' => $conflict
                ? 'Room already booked for selected time.'
                : 'Room is available.'

        ]);
    }
}