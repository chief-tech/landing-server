<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\User;
use App\ProviderDevice;
use Exception;
class SendPushNotification extends Controller
{
	/**
     * New Ride Accepted by a Driver.
     *
     * @return void
     */
    public function RideAccepted($request){
    	return $this->sendPushToUser($request->user_id, trans('api.push.request_accepted'));
    }
    /**
     * Driver Arrived at your location.
     *
     * @return void
     */
    public function Arrived($request){
        return $this->sendPushToUser($request->user_id, trans('api.push.arrived'));
    }
    /**
     * New Incoming request
     *
     * @return void
     */
    public function IncomingRequest($provider){
        return $this->sendPushToProvider($provider, trans('api.push.incoming_request'));
    }

    public function VerifyEmail($provider){
        return $this->sendPushToProvider($provider, trans('api.push.email_verify'));
    }
    public function VerifyUserEmail($user){
        return $this->sendPushToProvider($user, trans('api.push.email_verify'));
    }
    /**
     * Driver Documents verfied.
     *
     * @return void
     */
    public function DocumentsVerfied($provider_id){
        return $this->sendPushToProvider($provider_id, trans('api.push.document_verfied'));
    }
    /**
     * Money added to user wallet.
     *
     * @return void
     */
    public function WalletMoney($user_id, $money){
        return $this->sendPushToUser($user_id, $money.' '.trans('api.push.added_money_to_wallet'));
    }
    /**
     * Money charged from user wallet.
     *
     * @return void
     */
    public function ChargedWalletMoney($user_id, $money){
        return $this->sendPushToUser($user_id, $money.' '.trans('api.push.charged_from_wallet'));
    }
    /**
     * Sending Push to a user Device.
     *
     * @return void
     */
     public function sendPushToUser($user_id, $push_message){
       try{
   	    	$user = User::findOrFail($user_id);
          if($user->device_token != ""){
            $url = "https://fcm.googleapis.com/fcm/send";
            $token = $user->device_token;

          //  $token = 'dFMbDBu9-oU:APA91bGX3bev5rsPeBIAx9OBDupZqEX8VfC09cBjBU30SwrIQcw0cFZaN3kJkioBz6p2Wa_jq6-RhHMEFqb5DBOthA4aRpzPY2ybSGLbRJBwR9IRtEOpZz1M8Ea-ssc8Ykd76se3OT44';
            $message = array('data' => $push_message);
          //  var_dump($fields); die();
            //token is device_id of user to whom we want to send notification.
            $fields = array(
                 'to' => $token,
                 'data' => $message
               );
            //FCM SERVER KEY FOR USER APP IN AUTHORIZATION:KEY
            $headers = array(
                  'Authorization:key = AAAABJCAoaQ:APA91bF1cbLoMIzQPSKk14xvyiap8XOvoy-r1WTqTw-0TLt-314PRUIP_BQRJiOYUPewOxAyYT0aBQWNwZILSriBy6ucc17eULee-xdfL8TnhLTgawdKug9ZnrQE8HyB33-0eAtPIl2T',
                  'Content-Type: application/json'
                );
           $ch = curl_init();
           curl_setopt($ch, CURLOPT_URL, $url);
           curl_setopt($ch, CURLOPT_POST, true);
           curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
           curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
           curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,0);
           curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
           curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
           $result = curl_exec($ch);
           //var_dump($result); die();
           if($result === FALSE){
            // die('Curl Failed' . curl_error($ch));
             return response()->json(['error' => 'Curl Failed' . curl_error($ch)], 500);
         }
         curl_close($ch);
         return $result;
       }
     }
       catch(Exception $e){
       		return $e;
       	}
     }
    // public function sendPushToUser($user_id, $push_message){
    //
    // 	try{
    //
	  //   	$user = User::findOrFail($user_id);
    //
    //         if($user->device_token != ""){
    //
    // 	    	if($user->device_type == 'ios'){
    //
    // 	    		return \PushNotification::app('IOSUser')
    // 		            ->to($user->device_token)
    // 		            ->send($push_message);
    //
    // 	    	}elseif($user->device_type == 'android'){
    //
    // 	    		return \PushNotification::app('AndroidUser')
    // 		            ->to($user->device_token)
    // 		            ->send($push_message);
    //
    // 	    	}
    //         }
    //
    // 	} catch(Exception $e){
    // 		return $e;
    // 	}
    //
    // }
    /**
     * Sending Push to a user Device.
     *
     * @return void
     */
    public function sendPushToProvider($provider_id, $push_message){
    	try{
	    	$provider = ProviderDevice::where('provider_id',$provider_id)->first();
            if($provider->token != ""){
              $url = "https://fcm.googleapis.com/fcm/send";
              $token = $provider->token;
              //token is device_id of provider to whom we want to send notification.
              $message = array('data' => $push_message);
              $fields = array(
                   'to' => $token,
                   'data' => $message
                 );
          //      var_dump($fields);
              //FCM SERVER KEY FOR USER APP IN AUTHORIZATION:KEY
              $headers = array(
                    'Authorization:key =AAAABJCAoaQ:APA91bF1cbLoMIzQPSKk14xvyiap8XOvoy-r1WTqTw-0TLt-314PRUIP_BQRJiOYUPewOxAyYT0aBQWNwZILSriBy6ucc17eULee-xdfL8TnhLTgawdKug9ZnrQE8HyB33-0eAtPIl2T',
                    'Content-Type: application/json'
                  );
              $ch = curl_init();
              curl_setopt($ch, CURLOPT_URL, $url);
              curl_setopt($ch, CURLOPT_POST, true);
              curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
              curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
              curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,0);
              curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
              curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
              $result = curl_exec($ch);
            //  var_dump($result); die();
              if($result === FALSE){
                return response()->json(['error' => 'Curl Failed' . curl_error($ch)], 500);

              // die('Curl Failed' . curl_error($ch));
              }
              curl_close($ch);
              return $result;
            	// if($provider->type == 'ios'){
              //
            	// 	return \PushNotification::app('IOSProvider')
        	    //         ->to($provider->token)
        	    //         ->send($push_message);
              //
            	// }elseif($provider->type == 'android'){
              //
            	// 	return \PushNotification::app('AndroidProvider')
        	    //         ->to($provider->token)
        	    //         ->send($push_message);
              //
            	// }
            }
    	} catch(Exception $e){
    		return $e;
    	}
    }
}
