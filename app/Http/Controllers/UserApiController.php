<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Log;
use Auth;
use Hash;
use Setting;
use Exception;
use Storage;
use Carbon\Carbon;
use App\Http\Controllers\SendPushNotification;
use App\User;
use App\ProviderService;
use App\UserRequests;
use App\Promocode;
use App\RequestFilter;
use App\ServiceType;
use App\Provider;
use App\Settings;
use App\UserRequestRating;
use App\Card;
use App\PromocodeUsage;

use Mail;
class UserApiController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */

    public function login_access(Request $request){
      $this->validate($request, [
              'grant_type' => 'required',
              'client_secret' => 'required',
              'client_id' => 'required|max:255',
              'email' => 'nullable',
              'password' => 'nullable',
              'social_unique_id' => 'nullable',
          ]);
      $email = $request->email;
      if($request->email != "" && $request->password != ""){
      if(Auth::attempt(['email' => $email, 'password' => $request->password])){
            $user = Auth::user();
            $s = $user->createToken('MyApp');
            $token_type = 'Bearer';
            $access_token =  $s->accessToken;

            // $success['refresh_token'] =  $s->refreshToken;
            if(Auth::user()->confirmation != 0)
            return response()->json(['token_type' => $token_type, 'access_token' => $access_token], 200);
            else {
              Auth::logout();
              return response()->json(['error' => 'Your email is not verified'], 401);
            }
        }
        else{
          return response()->json(['error' => 'The email address or password you entered is incorrect.'], 400);
        }
      }
      else if($request->social_unique_id != ""){
        $authUser = User::where('social_unique_id', $request->social_unique_id)->first();
        if ($authUser) {
          Auth::login($authUser, true);
          $user = Auth::user();
          $s = $user->createToken('MyApp');
          $token_type = 'Bearer';
          $access_token =  $s->accessToken;
          if(Auth::user()->confirmation != 0)
          return response()->json(['token_type' => $token_type, 'access_token' => $access_token], 200);
          else {
            Auth::logout();
            return response()->json(['message' => 'Your email is not verified'], 401);
          }
        }
        else{
          return response()->json(['message' => 'User not found'], 401);
        }
      }
      else {
          return response()->json(['error' => 'Invalid Login Request'], 402);
        }

    }

    public function confirmation($token){
      try{
      $user = User::where('token', '=', $token)->first();

      if(!is_null($user)){
        $user->update(['confirmation' => 1,'token' => '',]);
        (new SendPushNotification)->UserEmailVerified($user);

        return response()->json(['message' => 'Your email is verified'], 200);

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
      $user = User::where('email', '=', $request->email)->first();

      if(!is_null($user)){
        $data = $user->toArray();
        if($user->token == ""){
          return response()->json(['message' => 'Your email is already verified'], 400);

        }
        $data['token'] = str_random(25);
        //$user = User::find($data['id']);
        $user->update(['token' => $data['token']]);

        Mail::send('user.mail.confirmation', $data, function($message) use($data){
              $message->to($data['email']);
              $message->subject('VERIFY EMAIL ADDRESS');
        });
        (new SendPushNotification)->VerifyUserEmail($user);
        return response()->json(['Verification Required' => 'An Email is send to your email address. Kindly verify email'], 200);

      }
      else {
        return response()->json(['error' => 'Email not found'], 402);

      }
    }
    catch(Exception $e){
      return response()->json(['error' => trans('api.something_went_wrong')], 500);

    }
    }
    public function signup(Request $request)
    {
        $this->validate($request, [
                'social_unique_id' => ['required_if:login_by,facebook,google','unique:users'],
                'device_type' => 'required|in:android,ios',
                'device_token' => 'required',
                'device_id' => 'required',
                'login_by' => 'required|in:manual,facebook,google',
                'first_name' => 'required|max:255',
                'last_name' => 'nullable|max:255',
                'email' => 'required|email|max:255|unique:users',
                'mobile' => 'required|digits_between:6,13',
                'password' => 'nullable|min:6',
                'social_unique_id' => 'nullable|unique:users',
            ]);
        try{
            $User = $request->all();
            $User['payment_mode'] = 'CASH';
            if($request->password != ""){
            $User['password'] = bcrypt($request->password);
          }
            $User = User::create($User);
            $data = $User->toArray();
            $user = User::find($data['id']);
            if($request->social_unique_id != ""){
              $user->update(['login_by' => 'facebook']);
            }

              $token = str_random(25);
              // $user = User::find($data['id']);
              $user = User::find($data['id']);
              $user->update(['token' => $token]);

              $data = $user->toArray();

            //  return response()->json(['data'=>$data['token']]);

            Mail::send('user.mail.confirmation', $data, function($message) use($data){
                  $message->to($data['email']);
                  $message->subject('VERIFY EMAIL ADDRESS');
            });
            (new SendPushNotification)->VerifyUserEmail($user);
          //  return redirect(url('login'))->with('status','A confirmation email has been send to your email address. Kindly check your email to verify.');
            return response()->json(['Verification Required' => 'An Email is send to your email address. Kindly verify email'], 200);

          //  return $User;
        } catch (Exception $e) {
             return response()->json(['error' => trans('api.something_went_wrong')], 500);
        }
    }
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function change_password(Request $request){
        $this->validate($request, [
                'password' => 'required|confirmed|min:6',
                'old_password' => 'required',
            ]);
        $User = Auth::user();
        if(Hash::check($request->old_password, $User->password))
        {
            $User->password = bcrypt($request->password);
            $User->save();
            if($request->ajax()) {
                return response()->json(['message' => trans('api.user.password_updated')]);
            }else{
                return back()->with('flash_success', 'Password Updated');
            }
        } else {
            return response()->json(['error' => trans('api.user.incorrect_password')], 500);
        }
    }
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function update_location(Request $request){
        $this->validate($request, [
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
            ]);
        if($user = User::find(Auth::user()->id)){
            $user->latitude = $request->latitude;
            $user->longitude = $request->longitude;
            $user->save();
            return response()->json(['message' => trans('api.user.location_updated')]);
        }else{
            return response()->json(['error' => trans('api.user.user_not_found')], 500);
        }
    }
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function details(Request $request){
        $this->validate($request, [
            'device_type' => 'in:android,ios',
        ]);
        try{
            if($user = User::find(Auth::user()->id)){
                if($request->has('device_token')){
                    $user->device_token = $request->device_token;
                }
                if($request->has('device_type')){
                    $user->device_type = $request->device_type;
                }
                if($request->has('device_id')){
                    $user->device_id = $request->device_id;
                }
                $user->save();
                $user->currency = currency();
                return $user;
            }else{
                return response()->json(['error' => trans('api.user.user_not_found')], 500);
            }
        }
        catch (Exception $e) {
            return response()->json(['error' => trans('api.something_went_wrong')], 500);
        }
    }
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function update_profile(Request $request)
    {
        $this->validate($request, [
                'first_name' => 'required|max:255',
                'last_name' => 'max:255',
                'email' => 'email|unique:users,email,'.Auth::user()->id,
                'mobile' => 'required|digits_between:6,13',
                'picture' => 'mimes:jpeg,bmp,png',
            ]);
         try {
            $user = User::findOrFail(Auth::user()->id);
            if($request->has('first_name')){
                $user->first_name = $request->first_name;
            }
            if($request->has('last_name')){
                $user->last_name = $request->last_name;
            }
            if($request->has('email')){
                $user->email = $request->email;
                $user->mobile = $request->mobile;
            }
            if ($request->picture != "") {
                Storage::delete($user->picture);
                $user->picture = $request->picture->store('user/profile');
            }
            $user->save();
            if($request->ajax()) {
                return response()->json($user);
            }else{
                return back()->with('flash_success', trans('api.user.profile_updated'));
            }
        }
        catch (ModelNotFoundException $e) {
             return response()->json(['error' => trans('api.user.user_not_found')], 500);
        }
    }
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function services() {
        if($serviceList = ServiceType::all()) {
            return $serviceList;
        } else {
            return response()->json(['error' => trans('api.services_not_found')], 500);
        }
    }
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function send_request(Request $request) {
        $this->validate($request, [
               's_latitude' => 'required|numeric',
               'd_latitude' => 'nullable|numeric',
               's_longitude' => 'required|numeric',
               'd_longitude' => 'nullable|numeric',
               'service_type' => 'required|numeric|exists:service_types,id',
               'promo_code' => 'exists:promocodes,promo_code',
               'distance' => 'nullable|numeric',
               'use_wallet' => 'numeric',
               'payment_mode' => 'required|in:CASH,CARD,PAYPAL',
               'card_id' => ['required_if:payment_mode,CARD','exists:cards,card_id,user_id,'.Auth::user()->id],
            ]);
        Log::info('New Request from user id :'. Auth::user()->id .' params are :');
        Log::info($request->all());
        $ActiveRequests = UserRequests::PendingRequest(Auth::user()->id)->count();
        if($ActiveRequests > 0) {
            if($request->ajax()) {
                return response()->json(['error' => trans('api.ride.request_inprogress')], 500);
            }else{
                return redirect('dashboard')->with('flash_error', 'Already request is in progress. Try again later');
            }
        }
        $ActiveProviders = ProviderService::AvailableServiceProvider($request->service_type)->get()->pluck('provider_id');
      //  var_dump($ActiveProviders);
//echo "******************************************************";
        $distance = Setting::get('search_radius', '10');
        $latitude = $request->s_latitude;
        $longitude = $request->s_longitude;
        $Providers = Provider::whereIn('id', $ActiveProviders)
            ->where('status', 'approved')
             ->whereRaw("(1.609344 * 3956 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance")
             ->get();
      // $theta = $lon1 - $lon2;
      // $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
      // $dist = acos($dist);
      // $dist = rad2deg($dist);
      // $miles = $dist * 60 * 1.1515;

        // List Providers who are currently busy and add them to the filter list.
        if(count($Providers) == 0) {
            if($request->ajax()) {
                // Push Notification to User
                return response()->json(['message' => trans('api.ride.no_providers_found')]);
            }else{
                return back()->with('flash_success', 'No Providers Found! Please try again.');
            }
        }
        try{
            $UserRequest = new UserRequests;
            $UserRequest->user_id = Auth::user()->id;
            $UserRequest->current_provider_id = $Providers[0]->id;
            $UserRequest->service_type_id = $request->service_type;
            $UserRequest->payment_mode = $request->payment_mode;
            $UserRequest->status = 'SEARCHING';
            $UserRequest->s_address = $request->s_address ? : "";
            $UserRequest->d_address = $request->d_address ? : "";
            $UserRequest->s_latitude = $request->s_latitude;
            $UserRequest->s_longitude = $request->s_longitude;
            if($request->d_latitude != "" && $request->d_longitude != ""){
             $UserRequest->d_latitude = $request->d_latitude;
             $UserRequest->d_longitude = $request->d_longitude;
             $UserRequest->distance = $request->distance;
           }
           else {
             $UserRequest->d_latitude = 0;
             $UserRequest->d_longitude = 0;
             $UserRequest->distance = 0;
           }
            $UserRequest->use_wallet = $request->use_wallet ? : 0;
            $UserRequest->assigned_at = Carbon::now();
            $UserRequest->save();
            Log::info('New Request id : '. $UserRequest->id .' Assigned to provider : '. $UserRequest->current_provider_id);
            // incoming request push to provider
            (new SendPushNotification)->IncomingRequest($UserRequest->current_provider_id);
            // update payment mode
            User::where('id',Auth::user()->id)->update(['payment_mode' => $request->payment_mode]);
            if($request->has('card_id')){
                Card::where('user_id',Auth::user()->id)->update(['is_default' => 0]);
                Card::where('card_id',$request->card_id)->update(['is_default' => 1]);
            }
            foreach ($Providers as $key => $Provider) {
                $Filter = new RequestFilter;
                // Send push notifications to the first provider
                // $title = Helper::get_push_message(604);
                // $message = "You got a new request from".$user->name;
                $Filter->request_id = $UserRequest->id;
                $Filter->provider_id = $Provider->id;
                $Filter->save();
            }
            if($request->ajax()) {
                return response()->json([
                        'message' => 'New request Created!',
                        'request_id' => $UserRequest->id,
                        'current_provider' => $UserRequest->current_provider_id,
                    ]);
            }else{
                return redirect('dashboard');
            }
        } catch (Exception $e) {
            if($request->ajax()) {
                return response()->json(['error' => trans('api.something_went_wrong')], 500);
            }else{
                return back()->with('flash_error', 'Something went wrong while sending request. Please try again.');
            }
        }
    }
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function cancel_request(Request $request) {
        $this->validate($request, [
                'request_id' => 'required|numeric|exists:user_requests,id,user_id,'.Auth::user()->id,
            ]);
        try{
            $UserRequest = UserRequests::findOrFail($request->request_id);
            if($UserRequest->status == 'CANCELLED')
            {
                if($request->ajax()) {
                    return response()->json(['error' => trans('api.ride.already_cancelled')], 500);
                }else{
                    return back()->with('flash_error', 'Request is Already Cancelled!');
                }
            }
            if(in_array($UserRequest->status, ['SEARCHING','STARTED','ARRIVED'])) {
                $UserRequest->status = 'CANCELLED';
                $UserRequest->save();
                RequestFilter::where('request_id', $UserRequest->id)->delete();
                if($UserRequest->provider_id != 0){
                    ProviderService::where('provider_id',$UserRequest->provider_id)->update(['status' => 'active']);
                    // send push and email
                }
                if($request->ajax()) {
                    return response()->json(['message' => trans('api.ride.ride_cancelled')]);
                }else{
                    return redirect('dashboard')->with('flash_success','Request Cancelled Successfully');
                }
            } else {
                if($request->ajax()) {
                    return response()->json(['error' => trans('api.ride.already_onride')], 500);
                }else{
                    return back()->with('flash_error', 'Service Already Started!');
                }
            }
        }
        catch (ModelNotFoundException $e) {
            if($request->ajax()) {
                return response()->json(['error' => trans('api.something_went_wrong')]);
            }else{
                return back()->with('flash_error', 'No Request Found!');
            }
        }
    }
    /**
     * Show the request status check.
     *
     * @return \Illuminate\Http\Response
     */
    public function request_status_check() {
        try{
            $check_status = ['CANCELLED'];
            $UserRequests = UserRequests::UserRequestStatusCheck(Auth::user()->id,$check_status)
                                        ->get()
                                        ->toArray();
            return response()->json(['data' => $UserRequests]);
        }
        catch (Exception $e) {
            return response()->json(['error' => trans('api.something_went_wrong')], 500);
        }
    }
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function rate_provider(Request $request) {
        $this->validate($request, [
                'request_id' => 'required|integer|exists:user_requests,id,user_id,'.Auth::user()->id,
                'rating' => 'required|integer|in:1,2,3,4,5',
                'comment' => 'max:255',
            ]);
        $UserRequests = UserRequests::where('id' ,$request->request_id)
                ->where('status' ,'COMPLETED')
                ->where('paid', 0)
                ->first();
        if ($UserRequests) {
            if($request->ajax()){
                return response()->json(['error' => trans('api.user.not_paid')], 500);
            } else {
                return back()->with('flash_error', 'Service Already Started!');
            }
        }
        try{
            $UserRequest = UserRequests::findOrFail($request->request_id);
            if($UserRequest->rating == null) {
                UserRequestRating::create([
                        'provider_id' => $UserRequest->provider_id,
                        'user_id' => $UserRequest->user_id,
                        'request_id' => $UserRequest->id,
                        'user_rating' => $request->rating,
                        'user_comment' => $request->comment,
                    ]);
            } else {
                $UserRequest->rating->update([
                        'user_rating' => $request->rating,
                        'user_comment' => $request->comment,
                    ]);
            }
            $UserRequest->user_rated = 1;
            $UserRequest->save();
            $average = UserRequestRating::where('provider_id', $UserRequest->provider_id)->avg('user_rating');
            Provider::where('id',$UserRequest->provider_id)->update(['rating' => $average]);
            // Send Push Notification to Provider
            if($request->ajax()){
                return response()->json(['message' => trans('api.ride.provider_rated')]);
            }else{
                return redirect('dashboard')->with('flash_success', 'Driver Rated Successfully!');
            }
        } catch (Exception $e) {
            if($request->ajax()){
                return response()->json(['error' => trans('api.something_went_wrong')], 500);
            }else{
                return back()->with('flash_error', 'Something went wrong');
            }
        }
    }
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function trips() {
        try{
            $UserRequests = UserRequests::UserTrips(Auth::user()->id)->get();
            //var_dump($UserRequests);die();
            if(!empty($UserRequests)){
                $map_icon = asset('asset/marker.png');
                foreach ($UserRequests as $key => $value) {
                    $UserRequests[$key]->static_map = "https://maps.googleapis.com/maps/api/staticmap?autoscale=1&size=320x130&maptype=terrian&format=png&visual_refresh=true&markers=icon:".$map_icon."%7C".$value->s_latitude.",".$value->s_longitude."&markers=icon:".$map_icon."%7C".$value->d_latitude.",".$value->d_longitude."&path=color:0x000000|weight:3|".$value->s_latitude.",".$value->s_longitude."|".$value->d_latitude.",".$value->d_longitude."&key=".env('GOOGLE_API_KEY');
                }
            }
            return $UserRequests;
        }
        catch (Exception $e) {
            return response()->json(['error' => trans('api.something_went_wrong')]);
        }
    }
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function forgot_password(Request $request){
        $this->validate($request, [
                'email' => 'required|email|exists:users,email',
            ]);
        try{
            // $user = User::where('email' , $email)->first();
            // $new_password = uniqid();
            // $user->password = Hash::make($new_password);
            // send mail
            return response()->json(['message' => 'New Password Sent to your mail!']);
        }
        catch(Exception $e){
                return response()->json(['error' => trans('api.something_went_wrong')], 500);
        }
    }
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function estimated_fare(Request $request){
        $this->validate($request,[
                's_latitude' => 'required|numeric',
                's_longitude' => 'required|numeric',
                'd_latitude' => 'nullable|numeric',
                'd_longitude' => 'nullable|numeric',
                'service_type' => 'required|numeric|exists:service_types,id',
            ]);
        try{
          if($request->d_latitude != "" && $request->d_longitude != ""){
            $details = "http://maps.googleapis.com/maps/api/distancematrix/json?origins=".$request->s_latitude.",".$request->s_longitude."&destinations=".$request->d_latitude.",".$request->d_longitude."&mode=driving&sensor=false";
            $json = file_get_contents($details);
            $details = json_decode($json, TRUE);
            $meter = $details['rows'][0]['elements'][0]['distance']['value'];
            $time = $details['rows'][0]['elements'][0]['duration']['text'];
            $miles = round($meter/1609.344497893);
            // $tax_percentage = \Setting::get('tax_percentage');
            // $commission_percentage = \Setting::get('commission_percentage');
            // $service_type = ServiceType::findOrFail($request->service_type);
            // $base_price = $service_type->fixed;
            // $price_per_mile = $service_type->price;
            // $price = $base_price + ($miles * $price_per_mile);
            // $price += ( $commission_percentage/100 ) * $price;
            // $tax_price = ( $tax_percentage/100 ) * $price;
            $tax_price = 0;
            $service_fee = Setting::get('service_fee');
            $base_fare = Setting::get('base_fare');
            $Distance = $miles * Setting::get('price_per_mile');
            $time_charges = $time * Setting::get('price_per_minute');
            $total = $service_fee + $base_fare + $Distance + $time_charges;
            return response()->json([
                    'estimated_fare' => round($total,2),
                    'distance' => $miles,
                    'time' => $time,
                    'tax_price' => $tax_price,
                    'base_price' => $base_fare,
                    'wallet_balance' => Auth::user()->wallet_balance
                ]);
        }
        else {
          return response()->json([
                  'estimated_fare' => 'N/A',
                  'distance' => '0',
                  'time' => 'N/A',
                  'tax_price' => 'N/A',
                  'base_price' => 'N/A',
                  'wallet_balance' => Auth::user()->wallet_balance
              ]);
        }
      }
        catch(Exception $e){
                return response()->json(['error' => trans('api.something_went_wrong')], 500);
        }
    }
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function trip_details(Request $request) {
         $this->validate($request, [
                'request_id' => 'required|integer|exists:user_requests,id',
            ]);
        try{
            $UserRequests = UserRequests::UserTripDetails(Auth::user()->id,$request->request_id)->get();
            if(!empty($UserRequests)){
                $map_icon = asset('asset/marker.png');
                foreach ($UserRequests as $key => $value) {
                    $UserRequests[$key]->static_map = "https://maps.googleapis.com/maps/api/staticmap?autoscale=1&size=320x130&maptype=terrian&format=png&visual_refresh=true&markers=icon:".$map_icon."%7C".$value->s_latitude.",".$value->s_longitude."&markers=icon:".$map_icon."%7C".$value->d_latitude.",".$value->d_longitude."&path=color:0x000000|weight:3|".$value->s_latitude.",".$value->s_longitude."|".$value->d_latitude.",".$value->d_longitude."&key=".env('GOOGLE_API_KEY');
                }
            }
            return $UserRequests;
        }
        catch (Exception $e) {
            return response()->json(['error' => trans('api.something_went_wrong')]);
        }
    }
    /**
     * get all promo code.
     *
     * @return \Illuminate\Http\Response
     */
    public function promocodes() {
        try{
            $this->check_expiry();
            $Promocode = PromocodeUsage::Active()->where('user_id',Auth::user()->id)
                                ->with('promocode')
                                ->get()
                                ->toArray();
            return response()->json($Promocode);
        }
        catch (Exception $e) {
            return response()->json(['error' => trans('api.something_went_wrong')], 500);
        }
    }
    public function check_expiry(){
        try{
            $Promocode = Promocode::all();
            foreach ($Promocode as $index => $promo) {
                if(date("Y-m-d") > $promo->expiration){
                    $promo->status = 'EXPIRED';
                    $promo->save();
                    PromocodeUsage::where('promocode_id',$promo->id)->update(['status' => 'EXPIRED']);
                }
            }
        }
        catch (Exception $e) {
            return response()->json(['error' => trans('api.something_went_wrong')], 500);
        }
    }
    /**
     * add promo code.
     *
     * @return \Illuminate\Http\Response
     */
    public function add_promocode(Request $request) {
         $this->validate($request, [
                'promocode' => 'required|exists:promocodes,promo_code',
            ]);
        try{
            $find_promo = Promocode::where('promo_code',$request->promocode)->first();
            if($find_promo->status == 'EXPIRED' || (date("Y-m-d") > $find_promo->expiration)){
                if($request->ajax()){
                    return response()->json([
                        'message' => trans('api.promocode_expired'),
                        'code' => 'promocode_expired'
                    ]);
                }else{
                    return back()->with('flash_error', trans('api.promocode_expired'));
                }
            }elseif(PromocodeUsage::where('promocode_id',$find_promo->id)->where('user_id', Auth::user()->id)->where('status','ADDED')->count() > 0){
                if($request->ajax()){
                    return response()->json([
                        'message' => trans('api.promocode_already_in_use'),
                        'code' => 'promocode_already_in_use'
                        ]);
                }else{
                    return back()->with('flash_error', 'Promocode Already in use');
                }
            }else{
                $promo = new PromocodeUsage;
                $promo->promocode_id = $find_promo->id;
                $promo->user_id = Auth::user()->id;
                $promo->status = 'ADDED';
                $promo->save();
                if($request->ajax()){
                    return response()->json([
                            'message' => trans('api.promocode_applied') ,
                            'code' => 'promocode_applied'
                         ]);
                }else{
                    return back()->with('flash_success', trans('api.promocode_applied'));
                }
            }
        }
        catch (Exception $e) {
            if($request->ajax()){
                return response()->json(['error' => trans('api.something_went_wrong')], 500);
            }else{
                return back()->with('flash_error', 'Something Went Wrong');
            }
        }
    }
}
