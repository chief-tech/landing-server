<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use App\User;

use Socialite;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' => 'logout']);
    }

    public function login(Request $request){
        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }
        $result = $this->attemptLogin($request);
        //var_dump($result);die();
      //  var_dump($request); die();

        if (Auth::user()->confirmation == 0) { // This is the most important part for you
            Auth::logout();
          return $this->sendEmailNotVerifiedResponse($request);
        }

        if ($result['login'] == true ) {

            return $this->sendLoginResponse($request);
        }
        //If email is not verified


        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        else{

        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
      }
    }

    /**
     * Show the application's login form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showLoginForm()
    {
        return view('user.auth.login');
    }

    protected function sendEmailNotVerifiedResponse(Request $request)
    {
        return redirect()->back()
            ->withInput($request->only($this->username(), 'remember'))
            ->withErrors([
                $this->username() => Lang::get('auth.EmailNotVerified'),
            ]);
    }
    // public function redirectToProvider($provider)
    // {
    //     return Socialite::driver($provider)->redirect();
    // }

    // public function handleProviderCallback($provider)
    // {
    //     $user = Socialite::driver($provider)->user();
    // //   dd($user);
    // //die("here");
    //     $authUser = $this->findOrCreateUser($user, $provider);
    //     Auth::login($authUser, true);
    //     return redirect($this->redirectTo);
    // }
    // public function findOrCreateUser($user, $provider)
    // {
    //     $authUser = User::where('social_unique_id', $user->id)->first();
    //     if ($authUser) {
    //         return $authUser;
    //     }
    //     return User::create([
    //         'first_name'     => $user->name,
    //         'email'    => $user->email,
    //         'social_unique_id' => $user->id,
    //         'confirmation' => 1
    //     ]);
    // }
}
