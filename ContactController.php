<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Room;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RoomController extends Controller
{

    public function index(Request $request)
    {
        $query = DB::table('room_view');

       
        if ($request->search) {

            $query->where('room_name', 'like',
                '%' . $request->search . '%');
        }

       
        if ($request->location) {

            $query->where('location_name',
                $request->location);
        }

        $rooms = $query
            ->orderBy('room_id', 'desc')
            ->paginate(10)
            ->withQueryString();

       
        $locations = DB::table('locations')
            ->select('location_id', 'location_name')
            ->orderBy('location_name')
            ->get();

        return view('admin.room_management',
            compact('rooms', 'locations'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'room_name' => 'required',
            'location_id' => 'required',
            'capacity' => 'required|integer|min:1'
        ]);

        try {

            DB::statement('CALL AddRoom(?, ?, ?)', [

                $request->room_name,
                $request->location_id,
                $request->capacity

            ]);

           
            $room = DB::table('rooms')
                ->where('room_name', $request->room_name)
                ->latest('room_id')
                ->first();

            
            DB::table('activity_logs')->insert([

                'user_id' => Auth::id(),
                'action' => 'Added Room: ' . $request->room_name,
                'timestamp' => now()

            ]);

            return redirect()->back()
                ->with('success', 'Room added successfully');

        } catch (\Exception $e) {

            return redirect()->back()
                ->withErrors([
                    'room_name' => $e->getMessage()
                ])
                ->withInput();
        }
    }


    public function update(Request $request, $id)
    {
        $room = Room::findOrFail($id);

        $request->validate([
            'room_name' => 'required|unique:rooms,room_name,' . $id . ',room_id',
            'location_id' => 'required',
            'capacity' => 'required|integer|min:1'
        ]);

        $room->update([
            'room_name' => $request->room_name,
            'location_id' => $request->location_id,
            'capacity' => $request->capacity
        ]);

        
        DB::table('activity_logs')->insert([
            'user_id' => Auth::id(),
            'action' => 'Updated Room: ' . $room->room_name,
            'timestamp' => now()
        ]);

        return redirect()->back()
            ->with('success', 'Room updated successfully');
    }


    public function destroy($id)
    {
        $room = Room::findOrFail($id);
        $roomName = $room->room_name;

        $room->delete();

        DB::table('activity_logs')->insert([
            'user_id' => Auth::id(),
            'action' => 'Deleted Room: ' . $roomName,
            'timestamp' => now()
        ]);

        return redirect()->back()->with('success', 'Room deleted successfully');
    }
}