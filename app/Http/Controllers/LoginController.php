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
            $CASattribute = phpCAS::getAttributes();
            
            $user = User::firstOrCreate(
                ['netid' => $netid],
                [
                    'netid' => $netid,
                    'email' => $CASattribute['mail'],
                ]
            );
            
            Auth::login($user);
            return redirect()->intended('landing');
        }
        
        return redirect()->route('landing')->with('error', 'CAS authentication failed');
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('landing');
    }

}
