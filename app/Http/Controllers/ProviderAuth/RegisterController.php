<?php

namespace App\Http\Controllers\ProviderAuth;

use App\Provider;
use Validator;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\SendPushNotification;
use App\Provider;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Auth;

use Mail;


class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = '/provider/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('provider.guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:providers',
            'password' => 'required|min:6|confirmed',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return Provider
     */
    protected function create(array $data)
    {
        return Provider::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);
    }

    /**
     * Show the application registration form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showRegistrationForm()
    {
        return view('provider.auth.register');
    }

    /**
     * Get the guard to be used during registration.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard('provider');
    }
    protected function register(Request $request){
      $input = $request->all();
      $validator = $this->validator($input);
      $validator->validate();
  //    var_dump($validator->passes());
      //die();
      if($validator->passes()){
        $data = $this->create($input)->toArray();
        $data['token'] = str_random(25);
        $provider = Provider::find($data['id']);
        $provider->token = $data['token'];
        $provider->save();

        Mail::send('provider.mail.confirmation', $data, function($message) use($data){
              $message->to($data['email']);
              $message->subject('VERIFY EMAIL ADDRESS');
        });
      //  (new SendPushNotification)->VerifyEmail($provider);
        return redirect(url('provider/login'))->with('status','A confirmation email has been send to your email address. Kindly check your email to verify.');

      }
      echo $validator->errors();
    //  die();
    //  return redirect(url('provider/login'))->with('status', $validator->errors());
    }

    public function confirmation($token){
      $provider = Provider::where('token', $token)->first();
      if(!is_null($provider)){
        $provider->confirmation =1;
        $provider->token = '';
        $provider->save();

        return redirect(url('provider/login'))->with('status','Congratulations, Your email is verified.');

      }
      return redirect(url('provider/login'))->with('status','Something went wrong.');

    }
}
