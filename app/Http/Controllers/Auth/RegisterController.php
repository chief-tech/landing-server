<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
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
     * Where to redirect users after registration.
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
        $this->middleware('guest');
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
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:6|confirmed',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {
        return User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'payment_mode' => 'CASH'
        ]);

        // send welcome email here
    }


    /**
     * Show the application registration form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showRegistrationForm()
    {
        return view('user.auth.register');
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
        $user = User::find($data['id']);
        $user->token = $data['token'];
        $user->save();

        Mail::send('provider.mail.confirmation', $data, function($message) use($data){
              $message->to($data['email']);
              $message->subject('VERIFY EMAIL ADDRESS');
        });
        //(new SendPushNotification)->VerifyUserEmail($user);
        return redirect(url('login'))->with('status','A confirmation email has been send to your email address. Kindly check your email to verify.');

      }
      echo $validator->errors();
    //  die();
    //  return redirect(url('provider/login'))->with('status', $validator->errors());
    }

    public function confirmation($token){
      $user = User::where('token', $token)->first();
      if(!is_null($user)){
        $user->confirmation =1;
        $user->token = '';
        $user->save();

        return redirect(url('login'))->with('status','Congratulations, Your email is verified.');

      }
      return redirect(url('login'))->with('status','Something went wrong.');

    }
}
