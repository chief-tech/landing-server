<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\UserRequestPayment;
use App\UserRequests;
use App\Card;
use App\User;
use App\ProviderAccount;
use App\Http\Controllers\SendPushNotification;

use Setting;
use Exception;
use Auth;

class PaymentController extends Controller
{
	/**
     * payment for user.
     *
     * @return \Illuminate\Http\Response
     */
    public function payment(Request $request){

    	$this->validate($request, [
    			'request_id' => 'required|exists:user_request_payments,request_id|exists:user_requests,id,paid,0,user_id,'.Auth::user()->id
    		]);


    	$UserRequest = UserRequests::find($request->request_id);

    	if($UserRequest->payment_mode == 'CARD'){

    		$RequestPayment = UserRequestPayment::where('request_id',$request->request_id)->first();

    		$StripeCharge = $RequestPayment->total * 100;


    		try{

    			$Card = Card::where('user_id',Auth::user()->id)->where('is_default',1)->first();

	    		// \Stripe\Stripe::setApiKey(Setting::get('stripe_secret_key'));
          //
	    		// $Charge = \Stripe\Charge::create(array(
					//   "amount" => $StripeCharge,
					//   "currency" => "usd",
					//   "customer" => Auth::user()->stripe_cust_id,
					//   "card" => $Card->card_id,
					//   "description" => "Payment Charge for ".Auth::user()->email,
					//   "receipt_email" => Auth::user()->email
					// ));
          \Stripe\Stripe::setApiKey(Setting::get('stripe_secret_key'));
          $commision = ($RequestPayment->total/100)*10;
          $commision = round($commision,2);
          $platform_charges = Setting::get('service_fee') + $commision;
          $platform_charges = $platform_charges * 100;
          $driver_charges = $StripeCharge - $platform_charges;
          $driver_account =  ProviderAccount::where('provider_id', '=', $UserRequest->provider_id)->firstOrFail();
          $Charge = \Stripe\Charge::create(array(
            "amount" => $StripeCharge,
            "currency" => "usd",
            "customer" => Auth::user()->stripe_cust_id,
          //  "source" => 'tok_visa',
          //  "card" => $Card->stripe_token,
  					"description" => "Payment Charge for ".Auth::user()->email,
  					"receipt_email" => Auth::user()->email,
            "destination" => array(
              "amount" => $driver_charges,
              "account" => $driver_account->stripe_acct_id,
            ),
          ));

	    		$RequestPayment->payment_id = $Charge["id"];
	    		$RequestPayment->payment_mode = 'CARD';
	    		$RequestPayment->save();

	    		$UserRequest->paid = 1;
	    		$UserRequest->status = 'COMPLETED';
	    		$UserRequest->save();

                if($request->ajax()){
            	   return response()->json(['message' => trans('api.paid')]);
                }else{
                    return redirect('dashboard')->with('flash_success','Paid');
                }

    		} catch(\Stripe\StripeInvalidRequestError $e){
                if($request->ajax()){
    			     return response()->json(['error' => $e->getMessage()], 500);
                }else{
                    return back()->with('flash_error',$e->getMessage());
                }
    		}

    	}
    }


    /**
     * add wallet money for user.
     *
     * @return \Illuminate\Http\Response
     */
    public function add_money(Request $request){

        $this->validate($request, [
                'amount' => 'required|integer',
                'card_id' => 'required|exists:cards,card_id,user_id,'.Auth::user()->id
            ]);

        try{

            $StripeWalletCharge = $request->amount * 100;

            \Stripe\Stripe::setApiKey(Setting::get('stripe_secret_key'));

            $Charge = \Stripe\Charge::create(array(
                  "amount" => $StripeWalletCharge,
                  "currency" => "usd",
                  "customer" => Auth::user()->stripe_cust_id,
                  "card" => $request->card_id,
                  "description" => "Adding Money for ".Auth::user()->email,
                  "receipt_email" => Auth::user()->email
                ));

            $update_user = User::find(Auth::user()->id);
            $update_user->wallet_balance += $request->amount;
            $update_user->save();

            Card::where('user_id',Auth::user()->id)->update(['is_default' => 0]);
            Card::where('card_id',$request->card_id)->update(['is_default' => 1]);

            //sending push on adding wallet money
            (new SendPushNotification)->WalletMoney(Auth::user()->id,currency($request->amount));

            if($request->ajax()){
               return response()->json(['message' => currency($request->amount).trans('api.added_to_your_wallet'), 'user' => $update_user]);
            }else{
                return redirect('wallet')->with('flash_success',currency($request->amount).' added to your wallet');
            }

        } catch(\Stripe\StripeInvalidRequestError $e){
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
    //       $platform_charges = Setting::get('service_fee') + ((($request->amount)/100)*10);
    //       $driver_charges = $request->amount - $platform_charges;
    //       $charge = \Stripe\Charge::create(array(
    //         "amount" => $request->amount,
    //         "currency" => "usd",
    //         "source" => Auth::user()->stripe_cust_id,
    //         "destination" => array(
    //           "amount" => $driver_charges,
    //           "account" => 'acct_1B1a6aH8bT0E5Y6s',
    //         ),
    //       ));
    //   }
    // }

}
