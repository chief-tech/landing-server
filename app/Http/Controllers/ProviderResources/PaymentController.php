<?php
namespace App\Http\Controllers\ProviderResources;

use Illuminate\Http\Request;
use App\UserRequestPayment;
use App\UserRequests;
use App\Card;
use App\User;
use App\Provider;
use App\ProviderAccount;
use App\Http\Controllers\SendPushNotification;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Setting;
use Exception;
use Auth;

class PaymentController extends Controller
{
  public function create_account(Request $request){
    $this->validate($request, [
        'country' => 'required',
        'city' => 'required',
        'address' => 'required',
        'postal_code' => 'required',
        'state' => 'required',
        'DOB' => 'required',
        'ssn_last_4' => 'required',
        'personal_id_no' => 'required',
        'account_holder_name' => 'required',
        'account_holder_type' => 'required',
        'routing_number' => 'required',
        'account_number' => 'required',
       ]);
    try{
      //$country = $request->country;
      $key = Setting::get('stripe_secret_key');
      \Stripe\Stripe::setApiKey($key);

      $acct_details = \Stripe\Account::create(array(
          "country" => $request->country,
          "type" => "custom"
      ));
      //return response()->json(['account' => $acct_details]);

      $dob = explode('-', $request->DOB);
      $provider_account = new ProviderAccount;
      $provider_account->provider_id = Auth::user()->id;
      $provider_account->stripe_acct_id = $acct_details->id;
      $provider_account->stripe_sk_key = $acct_details->keys->secret;
      $provider_account->stripe_pk_key = $acct_details->keys->publishable;
      $provider_account->tos_acceptance_date = Carbon::now();
      $provider_account->tos_acceptance_ip = $_SERVER['REMOTE_ADDR'];
      $provider_account->DOB = $request->DOB;
      $provider_account->state =$request->state;
      $provider_account->city = $request->city;
      $provider_account->country = $request->country;
      $provider_account->address = $request->address;
      $provider_account->ssn_last_4 = $request->ssn_last_4;
      $provider_account->postal_code = $request->postal_code;
      $provider_account->personal_id_no = $request->personal_id_no;
      //bank account details for stripe external acccount
      $provider_account->bank_account_holder_name = $request->account_holder_name;
      $provider_account->bank_account_holder_type = $request->account_holder_type;
      $provider_account->bank_routing_number = $request->routing_number;
      $provider_account->bank_account_number = $request->account_number;
      // $provider_account->created_at = Carbon::now();
      $provider_account->save();
//      return response()->json(['account' => $provider_account]);
      try{
      $provider_id = Auth::user()->id;
    //  return response()->json(['account' => $provider_id]);
      $ProviderAccount = ProviderAccount::where('provider_id', '=', $provider_id)->firstOrFail();
    //  return response()->json(['account' => $ProviderAccount->tos_acceptance_date]);
      //echo $ProviderAccount->stripe_acct_id;
      //die();
      //$id = $ProviderAccount['stripe_acct_id'];
      // return response()->json(['day' => $dob[0],
      //                                  'month' => $dob[1],
      //                                  'year' => $dob[2]]);

      $acct = \Stripe\Account::retrieve($ProviderAccount->stripe_acct_id);
      //$acct->tos_acceptance->date = Carbon::now()->toDateTimeString();
      $acct->tos_acceptance->date = time();
      $acct->tos_acceptance->ip = $ProviderAccount->tos_acceptance_ip;
      $acct->legal_entity->first_name = Auth::user()->first_name;
      $acct->legal_entity->last_name = Auth::user()->last_name;
      $acct->legal_entity->dob = array('day' => $dob[0],
                                       'month' => $dob[1],
                                       'year' => $dob[2]);
      $acct->legal_entity->type = $ProviderAccount->type;
      $acct->legal_entity->address->state = $ProviderAccount->state;
      $acct->legal_entity->address->city = $ProviderAccount->city;
      $acct->legal_entity->address->line1 = $ProviderAccount->address;
      $acct->legal_entity->address->postal_code = $ProviderAccount->postal_code;
      $acct->legal_entity->ssn_last_4 = $ProviderAccount->ssn_last_4;
      $acct->legal_entity->personal_id_number = $ProviderAccount->personal_id_no;
//489-36-8350
      $bank_token = \Stripe\Token::create(array(
            "bank_account" => array(
              "country" => $ProviderAccount->country,
              "currency" => "usd",
              "account_holder_name" => $request->account_holder_name,
              "account_holder_type" => $request->account_holder_type,
              "routing_number" => $request->routing_number,
              "account_number" => $request->account_number
            )
          ));
      $ext_details = $acct->external_accounts->create(array(
        "external_account" => $bank_token->id,
      ));
      $acct->save();
      $acct_updated = \Stripe\Account::retrieve($ProviderAccount->stripe_acct_id);


    //  return response()->json(['message' => $tok], 200);

      $ProviderAccount->status = $acct_updated->legal_entity->verification->status;
      $ProviderAccount->save();
      $provider = Provider::findOrFail($provider_id);
      $provider->stripe_account_status = 'CREATED';
      $provider->save();
      if($acct_updated->external_accounts->total_count > 0)
        return response()->json(['message' => 'Your account is created succesfully'], 200);
      else
        return response()->json(['message' => 'Your payouts are not enabled'], 400);

      }
      catch (ModelNotFoundException $e) {
          return response()->json(['error' => 'Unable to accept, Please try again later'],400);
      }
      // catch (Exception $e) {
      //     return response()->json(['error' => 'Connection Error'],400);
      // }




  }
   catch(\Stripe\StripeInvalidRequestError $e){
        if($request->ajax()){
             return response()->json(['error' => $e->getMessage()], 500);
        }else{
            return back()->with('flash_error',$e->getMessage());
        }
  }
}

  public function payout(Request $request){
    $provider_id = Auth::user()->id;
    $ProviderAccount = ProviderAccount::where('provider_id', '=', $provider_id)->firstOrFail();
    $account_id = $ProviderAccount->stripe_acct_id;
    \Stripe\Stripe::setApiKey(Setting::get('stripe_secret_key'));
    try{
      $balance = \Stripe\Balance::retrieve(array("stripe_account" => $account_id)
      );

      // $payout =   \Stripe\Payout::create(array(
      //             "amount" => 5,
      //             "currency" => "usd",
      //             "method" => "instant"
      //         ), array("stripe_account" => $account_id));
        return response()->json([$balance]);
      }
    catch(\Stripe\StripeInvalidRequestError $e){
        if($request->ajax()){
          return response()->json(['error' => $e->getMessage()], 500);
       }else{
         return back()->with('flash_error',$e->getMessage());
         }
      }
  }
  public function verify_account(Request $request){
    try{
      \Stripe\Stripe::setApiKey(Setting::get('stripe_secret_key'));
      $customer = \Stripe\Customer::create(
        array('email' => Auth::user()->email),
        array('stripe_account' => 'acct_1B1a6aH8bT0E5Y6s')
      );

// Fetching an account just needs the ID as a parameter
    $acct = \Stripe\Account::retrieve('acct_1B1a6aH8bT0E5Y6s');
    $acct->tos_acceptance->date = time();
    // Assumes you're not using a proxy
    $acct->tos_acceptance->ip = $_SERVER['REMOTE_ADDR'];
    $acct->legal_entity->first_name = Auth::user()->first_name;
    $acct->legal_entity->last_name = Auth::user()->last_name;
    $acct->legal_entity->dob = array('day' => 11, 'month' => 03, 'year' => 1995);
    $acct->legal_entity->type = 'individual';
    $acct->legal_entity->address->state ='California';
    $acct->legal_entity->address->city = 'Los Angeles';
    $acct->legal_entity->address->line1 = 'house 5, street 3';
    $acct->legal_entity->address->postal_code = '90001';
    $acct->legal_entity->ssn_last_4 = '1234';
    $acct->save();
    return response()->json(['customer' => $customer, 'account' => $acct]);

  }
  catch(\Stripe\StripeInvalidRequestError $e){
       if($request->ajax()){
            return response()->json(['error' => $e->getMessage()], 500);
       }else{
           return back()->with('flash_error',$e->getMessage());
       }
 }
  }
  // public function transfer_to_provider(Request $request){
  //   try{
  //       \Stripe\Stripe::setApiKey(Setting::get('stripe_secret_key'));
  //
  //       $charge = \Stripe\Charge::create(array(
  //         "amount" => $request->amount,
  //         "currency" => "usd",
  //         "source" => ,
  //         "destination" => array(
  //           "amount" => 877,
  //           "account" => "{CONNECTED_STRIPE_ACCOUNT_ID}",
  //         ),
  //       ));
  //   }
  // }
//class end
}
?>
