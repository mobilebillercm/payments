<?php
/**
 * Created by PhpStorm.
 * User: nkalla
 * Date: 14/09/18
 * Time: 12:19
 */

namespace App\domain\model;


use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{

    const INITIATED = "INITIATED";
    const REFUSED = "REFUSED";
    const ACCEPTED = "ACCEPTED";

    protected $table = 'payments';
    protected $fillable = ['b_id', 'user_payment_number', 'methodtype', 'price', 'service', 'paymentaccount', 'user', 'beneficiary', 'status', 'tries', 'userid', 'tenantid', 'created_at', 'updated_at'];

    public function __construct($b_id = null, $user_payment_number = null, PaymentMethodType $methodtype = null, Price $price = null, Service $service = null,
                                $paymentaccount = null, User $user = null, User $beneficiary = null, $status = null, $tries = null, $userid = null, $tenantid = null, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->b_id = $b_id;
        $this->user_payment_number = $user_payment_number;
        $this->methodtype = json_encode($methodtype,JSON_UNESCAPED_SLASHES);
        $this->price = json_encode($price,JSON_UNESCAPED_SLASHES);
        $this->service = json_encode($service,JSON_UNESCAPED_SLASHES);
        $this->paymentaccount = json_encode($paymentaccount,JSON_UNESCAPED_SLASHES);
        $this->user = json_encode($user,JSON_UNESCAPED_SLASHES);
        $this->beneficiary = json_encode($beneficiary,JSON_UNESCAPED_SLASHES);
        $this->status = $status;
        $this->tries = $tries;
        $this->userid = $userid;
        $this->tenantid = $tenantid;
    }
}
