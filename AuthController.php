<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReservationController extends Controller
{


    public function index(Request $request)
    {
        // AUTO COMPLETE PAST BOOKINGS
        DB::table('reservations')
            ->where('reservation_date', '<', Carbon::today())
            ->where('status', 'BOOKED')
            ->update([
                'status' => 'COMPLETED'
            ]);

        $query = DB::table('scheduleadmin_view');

 
        if ($request->period == 'past') {

            // HISTORY:
            // COMPLETED
            // CANCELLED
            // PAST BOOKINGS

            $query->where(function ($q) {

                $q->where('status', 'COMPLETED')
                  ->orWhere('status', 'CANCELLED')
                  ->orWhere(function ($sub) {

                      $sub->where('status', 'BOOKED')
                          ->where('reservation_date', '<', Carbon::today());

                  });

            });

        } else {

            // ACTIVE BOOKINGS ONLY

            $query->where('status', 'BOOKED')
                  ->where('reservation_date', '>=', Carbon::today());
        }

 
        if ($request->search) {

            $query->where(function ($q) use ($request) {

                $q->where('room_name', 'like', '%' . $request->search . '%')
                  ->orWhere('first_name', 'like', '%' . $request->search . '%')
                  ->orWhere('last_name', 'like', '%' . $request->search . '%');

            });
        }

   
        if ($request->sort == 'oldest') {

            $query->orderBy('reservation_date', 'asc');

        } else {

            $query->orderBy('reservation_date', 'desc');
        }

        $reservations = $query->paginate(10)->withQueryString();


        $instructors = DB::table('users')
            ->where('role_id', 3)
            ->get();

        $rooms = DB::table('rooms')->get();

        return view('admin.reservation_management', compact(
            'reservations',
            'instructors',
            'rooms'
        ));
    }


    public function store(Request $request)
    {

        $request->validate([
            'user_id' => 'required|exists:users,user_id',
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
                ->where('user_id', $request->user_id)
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
                    'conflict' => 'Instructor already has a schedule at this time.'
                ]);
            }

   
            DB::table('reservations')->insert([

                'user_id' => $request->user_id,
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

            return back()->with('success', 'Reservation successfully created.');

        } catch (\Exception $e) {

            DB::rollBack();

            return back()->withErrors([
                'error' => $e->getMessage()
            ]);
        }
    }


    public function cancel($id)
    {
        DB::beginTransaction();

        try {

            DB::table('reservations')
                ->where('reservation_id', $id)
                ->update([
                    'status' => 'CANCELLED'
                ]);


            DB::table('activity_logs')->insert([

                'user_id' => Auth::id(),
                'action' => 'Cancelled Reservation ID: ' . $id,
                'timestamp' => now()

            ]);

            DB::commit();

            return back()->with('success', 'Reservation cancelled.');

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

        return response()->json([

            'available' => !$conflict,

            'message' => $conflict
                ? 'Room already booked for selected time.'
                : 'Room is available.'

        ]);
    }
}