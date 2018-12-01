<?php
namespace App\Http\Controllers;
//require "../../../vendor/autoload.php";
use App\domain\model\Amount;
use App\domain\model\CreditCard;
use App\domain\model\Duration;
use App\domain\model\Payment;
use App\domain\model\PaymentMethodType;
use App\domain\model\Price;
use App\domain\model\Service;
use App\domain\model\User;
use App\Jobs\ProcessMessages;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Webpatser\Uuid\Uuid;

//
/*
 * Utils links
 *
 * https://www.bincodes.com/users/register/
 *
 *https://github.com/Propaganistas/Laravel-Phone#validation
 *
 * https://dev.infobip.com/number-lookup
 *
 * https://www.twilio.com/blog/2016/03/how-to-validate-phone-numbers-in-php-with-the-twilio-lookup-api.html
 * https://www.twilio.com/console/runtime/api-keys/create
 * https://www.twilio.com/docs/lookup/api
 */

//&phonenumber=233474566&country_code=cm&country_dialing_code=00237&holder=Nkalla%20Ehawe%20Didier
//&card_number=4187622953314810&holder=Nkalla&expiry_date=08-20&security_code=527
///////////////////////////////////////////////////////////////
///
/// Twilio Account
///
/// First name: Nkalla Ehawe
/// Last name: Didier Junior
/// email: mobile.biller.cm@gmail.com
/// Password: mobilebiller102030OK
/// phone: 691179154
///
///
///  Friendly Name : my first twilio api key
//Sid: SKa49ab27efbb036ac2a69bbc1ea830a0a
//Key Type: Master
//Secret: qDWG6CslKygL4SnU2s6sKhXZF3zmWzfQ
///

class ApiController extends Controller
{

    public function is_JSON($args) {
        json_decode($args);
        return (json_last_error());
    }

    public function makePaymentWithMobileBillerAcount(Request $request){


        //return $request->all();


        $validator = Validator::make($request->all(), [
            'service_id'=> 'required|string|min:1|max:150',
            'service_name'=> 'required|string|min:1',
            'time_unit'=> 'required|string|min:1',
            'time_value'=> 'required|string|min:1',
            'amount_curency'=> 'required|string|min:1',
            'amount_value'=> 'required|string|min:1',
            'tenantid'=> 'required|string|min:1',
            'beneficiarytenantid'=> 'required|string|min:1',
            'user_id'=> 'required|string|min:1',
            'mobilebilleraccount'=>'required|string|min:1',
            'password'=>'required|string|min:1',
            'user_payment_number'=>'required|integer'
        ]);

        if ($validator->fails()){

            return response(array('success'=>0, 'faillure' => 1, 'raison' => $validator->errors()->first()), 200);
        }

        $payments = Payment::where('user_payment_number', '=', $request->get('user_payment_number'))->where('userid', '=', $request->get('user_id'))->get();

        if(!(count($payments) === 0)){
            return response(array('success'=>0, 'faillure' => 1, 'raison' => 'Faked payement'), 200);
        }



        try {


            $duration = new Duration($request->get('time_unit'), $request->get('time_value'));
            $amount = new Amount($request->get('amount_curency'), $request->get('amount_value'));
            $price = new Price($amount,$duration);
            $service = new Service($request->get('service_id'), $request->get('service_name'), $request->get('service_description'));
            $user = new User($request->get('user_id'), $request->get('firstname'), $request->get('lastname'), $request->get('username'));
            $beneciciary = new User($request->get('beneficiary_id'), $request->get('beneficiary_firstname'), $request->get('beneficiary_lastname'), $request->get('beneficiary_username'));

            $paymentMethods = PaymentMethodType::where('name','=','MOBILEBILLERCM')->get();


            if(!(count($paymentMethods) === 1)){
                return response(array('success'=>0, 'faillure' => 1, 'raison' => 'Wrong number of records'), 200);
            }



            $client = new Client();

            $token = null;

            try {


                $tokenUrl = env('HOST_IDENTITY_AND_ACCESS') . '/oauth/token';


                $tokenData = $client->post($tokenUrl, [
                    'form_params' => [
                        'grant_type' => 'client_credentials',
                        'client_id' => env('IDENTITY_AND_ACCESS_CLIENT_ID'),
                        'client_secret' => env('IDENTITY_AND_ACCESS_CLIENT_SECRET'),
                    ],
                ]);

                $token = json_decode((string)$tokenData->getBody());

                $url = env('HOST_IDENTITY_AND_ACCESS').'/api/users/'.$request->get('username').'/verify';

                $res = $client->post($url, [
                    'headers' => [
                        'Authorization' => '' . $token->access_token,
                    ],
                    'form_params'=>$request->all()
                ]);


                $isjson = $this->is_JSON((string)$res->getBody());

                if(!($isjson == 0)){

                    return response(array('success'=>0, 'faillure'=>1, 'raison'=>'Authentication Failed 1'),200);
                }

                $login = json_decode( (string) $res->getBody());


                if($login->success === 0 and $login->faillure === 1){

                    return response(array('success'=>0, 'faillure'=>1, 'raison'=>'Authentication Failed 2 ' . $login->raison),200);

                }


            } catch (BadResponseException $e) {

                return response(array('success'=>0, 'faillure'=>1, 'raison'=>'Authentication Failed 3 ' . $e->getMessage()),200);

            }




            $payment = new Payment(Uuid::generate()->string, $request->get('user_payment_number'), $paymentMethods[0], $price, $service, $request->get('mobilebilleraccount'),
                $user, $beneciciary,Payment::INITIATED,0, $request->get('beneficiary_id'), $request->get('beneficiarytenantid'));

            $payment->save();


            $payment->mobilebillercreditaccount = $request->get('mobilebilleraccount');


            ProcessMessages::dispatch(env('SERVICE_PAYEMENT_WITH_MOBILE_BILLER_CREDIT_ACCOUNT_INITIATED_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'), json_encode($payment));


            return response(array('success'=>1, 'faillure' => 0, 'response' => 'Paiement effectue avec success'), 200);



        } catch (BadResponseException $e) {
            return response(array('success'=>0, 'faillure' => 1, 'raison' => $e->getMessage()), 200);
        }


    }

    public function confirmPaymentWithMobileBillerAcount(){

        $body = file_get_contents('php://input');



        $data = json_decode($body, true);





        $validator = Validator::make($data, [
            'paymentid'=> 'required|string|min:1|max:150',
        ]);

        if ($validator->fails()){

            return response(array('success'=>0, 'faillure' => 1, 'raison' => $validator->errors()->first()), 200);
        }


        $payments = Payment::where('b_id', '=', $data['paymentid'])->get();






        if((count($payments) === 0)){
            return response(array('success'=>0, 'faillure' => 1, 'raison' => 'Payment not found'), 200);
        }



        try {


            $payments[0]->status = Payment::ACCEPTED;
            $payments[0]->save();




        } catch (BadResponseException $e) {
            return response(array('success'=>0, 'faillure' => 1, 'raison' => $e->getMessage()), 200);
        }


        ProcessMessages::dispatch(env('SERVICE_PAYEMENT_WITH_MOBILE_BILLER_CREDIT_ACCOUNT_ACCEPTED_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'),
            json_encode($payments[0]));

        return response(array('success'=>1, 'faillure' => 0, 'response' => 'Paiement effectue avec success'), 200);

    }

    public function failPaymentWithMobileBillerAcount(){

        $body = file_get_contents('php://input');

        //return $body;

        $data = json_decode($body, true);



        $validator = Validator::make($data, [
            'paymentid'=> 'required|string|min:1|max:150',

        ]);

        if ($validator->fails()){

            return response(array('success'=>0, 'faillure' => 1, 'raison' => $validator->errors()->first()), 200);
        }


        //return $data['paymentid'];

        $payments = Payment::where('b_id', '=', $data['paymentid'])->get();

        //return json_encode($payments);

        if((count($payments) === 0)){
            return response(array('success'=>0, 'faillure' => 1, 'raison' => 'Payment not found'), 200);
        }



        try {


            $payments[0]->status = Payment::REFUSED;
            $payments[0]->save();



        } catch (BadResponseException $e) {
            return response(array('success'=>0, 'faillure' => 1, 'raison' => $e->getMessage()), 200);
        }


        ProcessMessages::dispatch(env('SERVICE_PAYEMENT_WITH_MOBILE_BILLER_CREDIT_ACCOUNT_REFUSED_EXCHANGE'), env('RABBIT_MQ_EXCHANGE_TYPE'),
            json_encode(array('payment'=>$payments[0], 'message'=>$data['message'])));


        return response(array('success'=>1, 'faillure' => 0, 'response' => 'Paiement effectue avec success'), 200);

    }

    public function passes_luhn_check($cc_number) {
        $checksum  = 0;
        $j = 1;
        for ($i = strlen($cc_number) - 1; $i >= 0; $i--) {
            $calc = substr($cc_number, $i, 1) * $j;
            if ($calc > 9) {
                $checksum = $checksum + 1;
                $calc = $calc - 10;
            }
            $checksum += $calc;
            $j = ($j == 1) ? 2 : 1;
        }
        if ($checksum % 10 != 0) {
            return false;
        }
        return true;
    }

    public function validatePaymentAccountWithPaymentMethodeType(Request $request, $paymentMethodeTypeId){

        $paymentMethodeTypes = PaymentMethodType::where('b_id', '=', $paymentMethodeTypeId)->get();
        if (!(count($paymentMethodeTypes) ===1)){
            return response(array('success'=>0, 'faillure' => 1, 'raison' =>'Payment Method Type Not Found'), 200);
        }

        $paymentMethodeType = $paymentMethodeTypes[0];

        if ($paymentMethodeType->active == 0){
            return response(array('success'=>0, 'faillure' => 1, 'raison' =>'Invalid Payment Method'), 200);
        }

        $validateur = Validator::make(
            $request->all(),['type'=> 'required|string|min:1|max:150'],
            [
                'type.required' => 'Le type de methode de paiement est obligatoire.',
                'type.max' => 'Le type de methode de paiement doit avoir au plus 150 caracteres.',
            ]
        );

        if ($validateur->fails()){
            return response(array('success'=>0, 'faillure' => 1, 'raison' => $validateur->errors()->first()), 200);
        }

        $type = $request->get('type');
        $validator = null;
        if ($type == PaymentMethodType::CREDIT_CARD){
            $validator = Validator::make(
                $request->all(),[
                    'card_number'=> 'required|string|min:1|max:20',
                    'holder'=> 'required|string|min:1|max:250',
                    'expiry_date'=> 'required|string|min:1|max:20',
                    'security_code'=> 'required|string|min:1|max:20',
                ]
            );
        }else if ($type == PaymentMethodType::MOBILE_MONEY){
            $validator = Validator::make(
                $request->all(),[
                    'phonenumber'=> 'required|string|min:1|max:20',
                    'country_code'=> 'required|string|min:1|max:10',
                    'country_dialing_code'=> 'required|string|min:1|max:10',
                    'holder'=> 'required|string|min:1|max:250',
                ]
            );
        }else{
            return response(array('success'=>0, 'faillure' => 1, 'raison' => "Type Non Permis"), 200);
        }

        if ($validator->fails()){
            return response(array('success'=>0, 'faillure' => 1, 'raison' => $validator->errors()->first()), 200);
        }

        if ($type == PaymentMethodType::CREDIT_CARD){

            if (!$this->passes_luhn_check($request->get('card_number'))){
                return response(array('success'=>0, 'faillure' => 1, 'raison' => "Invalid Card Number"), 200);
            }

            $api = json_decode($paymentMethodeType->api);
            $validationUrl = $api->validationUrl;

            $client = new Client();
            try {

                $response = $client->get($validationUrl.  env('BINCODES_FORMAT_AND_API_KEY'). '&cc=' . $request->get('card_number'), []);


                $isjson = $this->is_JSON((string)$response->getBody());

                if(!($isjson == 0)){
                    return response(array('success'=>0, 'faillure' => 1, 'raison' => (string)$response->getBody()), 200);
                }

                $retVal = json_decode((string)$response->getBody());
                if (!($retVal->valid === 'true')){
                    return response(array('success'=>0, 'faillure' => 1, 'raison' => 'Invalid Card'), 200);
                }


                if (!(strtoupper($retVal->card) === strtoupper($paymentMethodeType->provider))){
                    return response(array('success'=>0, 'faillure' => 1, 'raison' => 'Card Issuer Not match'), 200);
                }

                return response(array('success'=>1, 'faillure' => 0, 'response' => "Valid Data"
                    /*. PhoneNumber::make('670851573', 'CM')->getCountry(). (string)$response->getBody()*/), 200);

            } catch (BadResponseException $e) {
                return response(array('success'=>0, 'faillure' => 1, 'raison' => $e->getMessage()), 200);
            }



            //return response(array('success'=>1, 'faillure' => 0, 'response' => "Valid Data"), 200);

        }else if ($type == PaymentMethodType::MOBILE_MONEY){

            /*$phoneValidator = Validator::make(
                $request->all(),
                ['phonenumber' => 'phone:'.$request->get('country_code').',mobile',]
            );

            if ($phoneValidator->fails()){
                return response(array('success'=>0, 'faillure' => 1, 'raison' => json_encode($phoneValidator->errors()) . "Invalid Phone Number"), 200);
            }


            return response(array('success'=>1, 'faillure' => 0, 'response' => "Valid Data"), 200);*/

            $sid = env('TWILIO_SID');
            $token = env('TWILIO_AUTH_TOKEN');
            $twilio_client = new \Twilio\Rest\Client($sid,$token);
            $phone_number = $twilio_client->lookups->v1->phoneNumbers("+237233474566")->fetch(array("type" => "caller-name"));

            if (!(strtoupper($phone_number->countryCode) === strtoupper($request->get('country_code')))){
                return response(array('success'=>0, 'faillure' => 1, 'raison' => "Invalid Data " /*. $phone_number->countryCode . " | "  . $request->get('country_code')*/), 200);
            }

            return response(array('success'=>1, 'faillure' => 0, 'response' => "Valid Data"), 200);
        }

    }
}

