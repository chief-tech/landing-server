<?php

namespace App\Http\Controllers\ProviderAuth;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Controllers\SendPushNotification;
use Tymon\JWTAuth\Exceptions\JWTException;

use Auth;
use Config;
use JWTAuth;
use Mail;

use App\Provider;
use App\ProviderDevice;

class TokenController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */

    public function register(Request $request)
    {
        $this->validate($request, [
                'device_id' => 'required',
                'device_type' => 'required|in:android,ios',
                'device_token' => 'required',
                'first_name' => 'required|max:255',
                'last_name' => 'required|max:255',
                'email' => 'nullable|email|max:255|unique:providers',
                'mobile' => 'required|digits_between:6,13',
                'password' => 'nullable|min:6|confirmed',
                'social_unique_id' => 'nullable|unique:providers',
            ]);

        try{

            $Provider = $request->all();
            if($request->password != ""){
            $Provider['password'] = bcrypt($request->password);
            }
            $Provider = Provider::create($Provider);

            ProviderDevice::create([
                    'provider_id' => $Provider->id,
                    'udid' => $request->device_id,
                    'token' => $request->device_token,
                    'type' => $request->device_type,
                ]);

                if($request->social_unique_id != ""){
                  $Provider->social_unique_id = $request->social_unique_id;
                  $Provider->confirmation = 1;
                }
                $provider_data = $request->all();
                $data = $Provider->toArray();
                $data['token'] = str_random(25);
                $provider = Provider::find($data['id']);
                $provider->token = $data['token'];

                $provider->save();

                Mail::send('provider.mail.confirmation', $data, function($message) use($data){
                      $message->to($data['email']);
                      $message->subject('VERIFY EMAIL ADDRESS');
                });

          //  return $Provider;
          (new SendPushNotification)->VerifyProviderEmail($provider);
          return response()->json(['Verification Required' => 'An Email is send to your email address. Kindly verify email'], 401);


        } catch (QueryException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Something went wrong, Please try again later!'], 500);
            }
            return abort(500);
        }

    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */

    public function authenticate(Request $request)
    {
        $this->validate($request, [
                'device_id' => 'required',
                'device_type' => 'required|in:android,ios',
                'device_token' => 'required',
                'email' => 'required|email',
                'password' => 'required|min:6',
            ]);

        Config::set('auth.providers.users.model', 'App\Provider');

        $credentials = $request->only('email', 'password');

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'The email address or password you entered is incorrect.'], 401);
            }
            else{
              $provider = Provider::find(Auth::user()->id);
              if($provider->confirmation == 0){
                Auth::logout();
                return response()->json(['error' => 'Your email is not verified'], 401);

              }
            }


        } catch (JWTException $e) {
            return response()->json(['error' => 'Something went wrong, Please try again later!'], 500);
        }

        $User = Provider::with('service', 'device')->find(Auth::user()->id);
        $User->access_token = $token;
        $User->currency = currency();

        if($User->device) {
            if($User->device->token != $request->token) {
                $User->device->update([
                        'udid' => $request->device_id,
                        'token' => $request->device_token,
                        'type' => $request->device_type,
                    ]);
            }
        } else {
            ProviderDevice::create([
                    'provider_id' => $User->id,
                    'udid' => $request->device_id,
                    'token' => $request->device_token,
                    'type' => $request->device_type,
                ]);
        }

        return response()->json($User);
    }
    public function confirmation($token){
      try{
      $provider = Provider::where('token', '=', $token)->first();

      if(!is_null($provider)){
        // $provider->update(['confirmation' => 1,'token' => '',]);
        $provider->confirmation = 1;
        $provider->token='';
        $provider->save();
        (new SendPushNotification)->ProviderEmailVerified($provider);

        return response()->json(['message' => 'Your email is verified'], 400);

      }
      else {
        return response()->json(['error' => 'This Link is expired'], 403);

      }
    }
    catch(Exception $e){
      return response()->json(['error' => trans('api.something_went_wrong')], 500);

    }

    }
    public function resend_email(Request $request){
      $this->validate($request, [
              'email' => 'required',
          ]);
      try{
      $provider = Provider::where('email', '=', $request->email)->first();

      if(!is_null($provider)){
        $data = $provider->toArray();
        if($provider->token == ""){
          return response()->json(['message' => 'Your email is already verified'], 400);

        }
        $data['token'] = str_random(25);
        //$user = User::find($data['id']);
      //  $provider->update(['token' => $data['token']]);
      $provider->token = $data['token'];
      $provider->save();
        Mail::send('provider.mail.confirmation', $data, function($message) use($data){
              $message->to($data['email']);
              $message->subject('VERIFY EMAIL ADDRESS');
        });
        (new SendPushNotification)->VerifyProviderEmail($provider);

        return response()->json(['Verification Required' => 'An Email is send to your email address. Kindly verify email'], 401);

      }
      else {
        return response()->json(['error' => 'Email not found'], 402);

      }
    }
    catch(Exception $e){
      return response()->json(['error' => trans('api.something_went_wrong')], 500);

    }
    }
}
