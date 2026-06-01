<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class AuthController extends Controller
{

    public function showLogin()
    {
        return view('login');
    }


    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {

            $request->session()->regenerate();

            // ACTIVITY LOG
            DB::table('activity_logs')->insert([
                'user_id' => Auth::id(),
                'action' => 'Logged In',
                'timestamp' => now()
            ]);

            $user = Auth::user();

            // ADMIN
            if ($user->role_id == 1) {
                return redirect('/admin/admin');
            }

            // INSTRUCTOR
            if ($user->role_id == 3) {
                return redirect('/instructor/instructor');
            }

            // INVALID ROLE
            Auth::logout();

            return redirect('/login')
                ->with('error', 'Invalid role assigned');
        }

        return back()
            ->with('error', 'Invalid username or password');
    }


    public function showSignup()
    {
        return view('signup');
    }


    public function signup(Request $request)
    {

        $request->validate([

            'first_name' => 'required|max:50',

            'last_name'  => 'required|max:50',

            'username'   => 'required|unique:users|max:50',

            'password'   => 'required|min:6',

            'contact_no' => [
                'required',
                'unique:users,contact_no',
                'regex:/^09\d{9}$/'
            ],

            'terms' => 'accepted'

        ], [

            'contact_no.regex' =>
                'Contact number must start with 09 and contain 11 digits.',

            'contact_no.unique' =>
                'Contact number already exists.',

            'terms.accepted' =>
                'You must agree to the Terms and Privacy Policy.'
        ]);


        $user = User::create([

            'role_id' => 3, // instructor

            'first_name' => $request->first_name,

            'last_name' => $request->last_name,

            'gender' => $request->gender,

            'dob' => $request->dob,

            'username' => $request->username,

            'password' => Hash::make($request->password),

            'contact_no' => $request->contact_no,

            'address' => $request->address

            
        ]);

        // ACTIVITY LOG
        DB::table('activity_logs')->insert([
            'user_id' => $user->user_id,
            'action' => 'Registered Account',
            'timestamp' => now()
        ]);

        return redirect('/login')
            ->with('success', 'Account created successfully');
    }


    public function logout(Request $request)
    {
        // ACTIVITY LOG
        DB::table('activity_logs')->insert([
            'user_id' => Auth::id(),
            'action' => 'Logged Out',
            'timestamp' => now()
        ]);

        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}