<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;


class UserController extends Controller
{
    
    public function index(Request $request)
    {
        $query = DB::table('user_view');

    
        if ($request->search) {

            $query->where(function ($q) use ($request) {

                $q->where('user_id', 'like', '%' . $request->search . '%')
                ->orWhere('first_name', 'like', '%' . $request->search . '%')
                ->orWhere('last_name', 'like', '%' . $request->search . '%')
                ->orWhere('username', 'like', '%' . $request->search . '%')
                ->orWhere('contact_no', 'like', '%' . $request->search . '%');

            });
        }

        
        if ($request->role) {

            $query->where('role_name', $request->role);

        }

        $users = $query->orderBy('user_id', 'desc')
    ->paginate(10)
    ->withQueryString();

        $roles = DB::table('roles')->get();

        return view('admin.user_management', compact('users', 'roles'));
    }

    
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'username' => 'required|unique:users',
            'password' => 'required|min:6',
            'role_id' => 'required',
            'dob' => 'required|date',

            'contact_no' => [
                'required',
                'unique:users',
                'regex:/^09[0-9]{9}$/'
            ],
        ]);

        $user = User::create([
            'role_id' => $request->role_id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'gender' => $request->gender,
            'dob' => $request->dob,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'contact_no' => $request->contact_no,
            'address' => $request->address
        ]);

        
        DB::table('activity_logs')->insert([
            'user_id' => Auth::id(),
            'action' => 'Added User: ' . $user->username,
            'timestamp' => now()
        ]);

        return redirect()->route('user_management')
            ->with('success', 'User added successfully');
    }

    
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        $username = $user->username;

        $user->delete();

        
        DB::table('activity_logs')->insert([
            'user_id' => Auth::id(),
            'action' => 'Deleted User: ' . $username,
            'timestamp' => now()
        ]);

        return redirect()->route('user_management')
            ->with('success', 'User deleted successfully');
            }
    

    public function show($id)
    {
        $user = User::findOrFail($id);

        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([

            'first_name' => 'required|max:50',
            'last_name' => 'required|max:50',

            'username' => 'required|unique:users,username,' . $id . ',user_id',
            'password' => 'nullable|min:6',
            'dob' => 'required|date',
            'contact_no' => [
                'required',
                'regex:/^09[0-9]{9}$/',
                'unique:users,contact_no,' . $id . ',user_id'
            ],
            'gender' => 'required|in:male,female',
            'role_id' => 'required'
        ]);

        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->gender = $request->gender;
        $user->dob = $request->dob;
        $user->username = $request->username;
        $user->contact_no = $request->contact_no;
        $user->address = $request->address;
        $user->role_id = $request->role_id;


        if ($request->password) {

            $user->password = Hash::make($request->password);

        }

        $user->save();

 
        DB::table('activity_logs')->insert([
            'user_id' => Auth::id(),
            'action' => 'Updated User: ' . $user->username,
            'timestamp' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully'
        ]);
    }
}