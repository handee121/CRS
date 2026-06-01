<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class IReservationController extends Controller
{

    public function myReservations(Request $request)
    {
        $query = DB::table('reservations')
            ->join('rooms', 'reservations.room_id', '=', 'rooms.room_id')
            ->where('reservations.user_id', Auth::id());

        // 🟢 DEFAULT = ACTIVE (if no filter selected)
        if (!$request->type || $request->type == 'active') {

            $query->where('reservations.status', 'BOOKED')
                ->whereDate('reservations.reservation_date', '>=', now());

        }

        if ($request->type == 'history') {

            $query->where(function ($q) {
                $q->where('reservations.status', 'CANCELLED')
                ->orWhere('reservations.status', 'COMPLETED')
                ->orWhereDate('reservations.reservation_date', '<', now());
            });
        }

        if ($request->room_id) {
            $query->where('reservations.room_id', $request->room_id);
        }

        if ($request->sort == 'asc') {
            $query->orderBy('reservations.created_at', 'asc');
        } else {
            $query->orderBy('reservations.created_at', 'desc');
        }

        $reservations = $query->select(
                'reservations.reservation_id',
                'rooms.room_name',
                'reservations.reservation_date',
                'reservations.start_time',
                'reservations.end_time',
                'reservations.status',
                'reservations.created_at'
            )
            ->paginate(10)
            ->withQueryString();

        $rooms = DB::table('rooms')->get();

        return view('instructor.my_reservation', compact('reservations', 'rooms'));
    }


    public function cancel($id)
    {
        DB::beginTransaction();

        try {

            $reservation = DB::table('reservations')
                ->where('reservation_id', $id)
                ->where('user_id', Auth::id()) 
                ->first();


            if (!$reservation) {

                DB::rollBack();

                return back()->withErrors([
                    'error' => 'Reservation not found.'
                ]);
            }


            if ($reservation->status !== 'BOOKED') {

                DB::rollBack();

                return back()->withErrors([
                    'error' => 'Only active reservations can be cancelled.'
                ]);
            }

            DB::table('reservations')
                ->where('reservation_id', $id)
                ->update([
                    'status' => 'CANCELLED'
                ]);


            DB::table('activity_logs')->insert([

                'user_id' => Auth::id(),

                'action' => 'Cancelled Own Reservation ID: ' . $id,

                'timestamp' => now()

            ]);

            DB::commit();

            return back()->with('success', 'Reservation cancelled successfully.');

        } catch (\Exception $e) {

            DB::rollBack();

            return back()->withErrors([
                'error' => $e->getMessage()
            ]);
        }
    }
}