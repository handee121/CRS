<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProfileAdmin extends Controller
{

    public function index()
    {
        $userId = Auth::id();

        $profile = DB::table('user_profile_view')
            ->where('user_id', Auth::id())
            ->first();

                return view('admin.profile', compact('profile'));
            }


    public function update(Request $request)
    {
        $request->validate([

            'first_name' => 'required|max:50',
            'last_name' => 'required|max:50',

            'username' => 'required|max:50',

            'contact_no' => [
                'required',
                'regex:/^09\d{9}$/'
            ],

            'address' => 'required|max:100',

            'dob' => 'nullable|date',

            'password' => 'nullable|min:6|confirmed',

        ], [
            'contact_no.regex' =>
                'Contact number must start with 09 and contain 11 digits.'
        ]);

        
        $user = DB::table('users')
            ->where('user_id', Auth::id())
            ->first();

        $data = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'gender' => $request->gender,
            'username' => $request->username,
            'contact_no' => $request->contact_no,
            'address' => $request->address,
            'dob' => $request->dob
        ];

    
        if ($request->password) {
            $data['password'] = Hash::make($request->password);
        }

        DB::table('users')
            ->where('user_id', Auth::id())
            ->update($data);

        // ACTIVITY LOG
        DB::table('activity_logs')->insert([
            'user_id' => Auth::id(),
            'action' => 'Updated Profile (' . $request->username . ')',
            'timestamp' => now()
        ]);

        return back()->with('success', 'Profile updated successfully');
    }
}