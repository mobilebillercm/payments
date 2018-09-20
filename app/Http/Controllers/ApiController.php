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

    public function makePayment(Request $request){

        // Todo Secutity Issue : Replay

        $validator = Validator::make($request->all(), [
            'service_id'=> 'required|string|min:1|max:150',
            'service_name'=> 'required|string|min:1',
            'time_unit'=> 'required|string|min:1',
            'time_value'=> 'required|string|min:1',
            'amount_curency'=> 'required|string|min:1',
            'amount_value'=> 'required|string|min:1',
            'payment_method_id'=> 'required|string|min:1',
            'card_number'=> 'required|string|min:1',
            'card_holder'=> 'required|string|min:1',
            'user_id'=> 'required|string|min:1',

        ]);

        if ($validator->fails()){

            return response(array('success'=>0, 'faillure' => 1, 'raison' => $validator->errors()->first()), 200);
        }

        // Payment metod

        $paymentMethods = PaymentMethodType::where('b_id', '=', $request->get('payment_method_id'))->get();
        if (!(count($paymentMethods) === 1)){
            return response(array('success'=>0, 'faillure' => 1, 'raison' => 'Payment method not found'), 200);
        }

        $paymentMethod = $paymentMethods[0];
        $api = $paymentMethod->api;
        $url = 'https://jsonplaceholder.typicode.com/posts'; //Todo $api->paymentUrl;


        $client = new Client();
        try {

            $params = array('title'=>$request->get('service_name'), 'body'=>$request->get('amount_value') . " | " . $request->get('card_number'), 'userId'=>1);

            $response = $client->post($url, [
                'headers'=>[
                    "Content-type"=>"application/json; charset=UTF-8",
                ],
                'body'=>json_encode($params)
            ]);

            //return (string)$response->getBody();

            $isjson = $this->is_JSON((string)$response->getBody());

            if(!($isjson == 0)){
                return response(array('success'=>0, 'faillure' => 1, 'raison' => (string)$response->getBody()), 200);
            }

            $duration = new Duration($request->get('time_unit'), $request->get('time_value'));
            $amount = new Amount($request->get('amount_curency'), $request->get('amount_value'));
            $price = new Price($amount,$duration);

            $service = new Service($request->get('service_id'), $request->get('service_name'), $request->get('service_description'));

            $user = new User($request->get('user_id'), $request->get('firstname'), $request->get('lastname'), $request->get('username'));

            $card = new CreditCard(Uuid::generate()->string, $request->get('card_number'),
                $request->get('card_holder'), $request->get('expiry_date'), $request->get('security_code'), $request->get('issuer'),
                1, $user);

            $payment = new Payment(Uuid::generate()->string, $paymentMethod, $price,$service, $card, $user, $user,true,0);

            $payment->save();
            //Todo Dispatch to service validation
            return response(array('success'=>1, 'faillure' => 0, 'response' => (string)$response->getBody()), 200);

            /*$result = (string)$response->getBody();

            $myresponse = json_decode($result, true);

            $key = 'response';
            if($myresponse['success'] === 0 and $myresponse['faillure']===1){
                $key = 'raison';
            }

            return Redirect::back()->with('message',array('receiveResultStatusCode' => 200,
                'result'=>array("success"=>$myresponse['success'], 'faillure'=>$myresponse['faillure'], "$key"=>$myresponse[$key])));

            //return response(array('success'=>1, 'faillure' => 0, 'response' => $response->getBody()), 200);*/

        } catch (BadResponseException $e) {
            return response(array('success'=>0, 'faillure' => 1, 'raison' => $e->getMessage()), 200);
        }


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
