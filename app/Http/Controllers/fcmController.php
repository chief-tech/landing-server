<?php
namespace App\Http\Controllers;
// use Illuminate\Http\Request;
//
// use LaravelFCM\Message\OptionsBuilder;
// use LaravelFCM\Message\PayloadDataBuilder;
// use LaravelFCM\Message\PayloadNotificationBuilder;
// use FCM;
// use Exception;


class fcmController extends Controller{

  public function notify(){
    $token = "f-BNgj0Qyd0:APA91bE0tchN4UEePgEmH3kqRJ4Y1ahAVAMwHTzpyw3MDuNVRSJ7g0KT-GZzwrzH8-Xdv18sRLjOrrFwgecwP3t1cerzK7aT7h2XhmPAQwcTh3JgN7ZbKO4dyQYlexzjG7XDFWzTRVId";
    $message = array("message" => "TEST NOTIFICATION FOR PROVIDER FROM LARAVEL");
    $res = $this->sendNotification($token, $message);
    var_dump($res);die();

  }

public function sendNotification($token, $message){
  $url = "https://fcm.googleapis.com/fcm/send";
  $fields = array(
            'to' => $token,
            'data' => $message
          );
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
    if($result === FALSE){
    //  echo "if block";
    //  die('Curl Failed' . curl_error($ch));
      return response()->json(['error' => 'Curl Failed' . curl_error($ch)], 500);

    }
    curl_close($ch);
  //  echo "outside if";
    return $result;
}
// public function sendNotification(){
//   $optionBuilder = new OptionsBuilder();
//   $optionBuilder->setTimeToLive(60*20);
//
//   $notificationBuilder = new PayloadNotificationBuilder('my title');
//   $notificationBuilder->setBody('Hello world')
//   				    ->setSound('default');
//
//   $dataBuilder = new PayloadDataBuilder();
//   $dataBuilder->addData(['a_data' => 'my_data']);
//
//   $option = $optionBuilder->build();
//   $notification = $notificationBuilder->build();
//   $data = $dataBuilder->build();
//
//   $token = "ejbHn3obZJ0:APA91bGn5txPxa2HKndDe4O6vMse4ISNGVZ_CE6LlzWWc1gMc3FcIFeHPedot24Pg0aovi21D9hWIYuDQs26xyZblNpbyMjVM7qQuDYqDRsbw3TDAm1k0zCcaM8ef5km_Yfy2Lfqtqjl";
//
//   $downstreamResponse = FCM::sendTo($token, $option, $notification, $data);
// var_dump($downstreamResponse);
// echo $downstreamResponse->numberSuccess();
// echo $downstreamResponse->numberFailure();
// echo $downstreamResponse->numberModification();
// $notificationBuilder = new PayloadNotificationBuilder();
// $notificationBuilder->setTitle('title')
//             		->setBody('body')
//             		->setSound('sound')
//             		->setBadge('badge');
//
// $notification = $notificationBuilder->build();
// $downstreamResponse = FCM::sendTo($token, $option, $notification);
// echo $downstreamResponse->numberSuccess();
// echo $downstreamResponse->numberFailure();
// echo $downstreamResponse->numberModification();
// var_dump($notification);
// }
}
?>
