<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use phpCAS;
include_once('/Users/soonwookwon/Developer/i3/i3-timetracker/config/cas.php');

class LoginController extends Controller
{
    public function login()
    {

        if ( Auth::check() ) {
            return redirect()->route('landing'); 
        }

        phpCAS::setLogger();
        phpCAS::setVerbose(true);

        phpCAS::client(
            SAML_VERSION_1_1, 
            "login.uconn.edu", 
            443, 
            "cas", 
            "https://");

        $cas_cert = config('cas.ca_cert_path');
        phpCAS::setCasServerCACert($cas_cert);
        // phpCAS::setNoCasServerValidation();

        phpCAS::forceAuthentication();

                
        if (\phpCAS::isAuthenticated()) {
            $netid = \phpCAS::getUser();
            
            $user = User::firstOrCreate(
                ['netid' => $netid],
                [
                    'name' => $netid,
                    'email' => $netid . '@uconn.edu' 
                ]
            );
            
            Auth::login($user);
            return redirect()->intended('landing');
        }
        
        return redirect()->route('landing')->with('error', 'CAS authentication failed');
    }

    /**
     * Handle an authentication attempt.
     */
    public function submitLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended(route('landing'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    } 

    /**
     * Log the user out.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('landing')->with('success', 'You have been logged out successfully.');
    }

    /**
     * Show the registration form.
     */
    public function register()
    {
        return view('auth.register'); // not made yet
    }

    /**
     * Handle a registration request.
     */
    public function submitRegister(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'netid' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::create([
            'name' => $request->name,
            'netid' => $request->netid,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'active' => true,
        ]);

        return redirect()->route('landing')->with('success', 'Registration successful. Please login.');
    }

}
