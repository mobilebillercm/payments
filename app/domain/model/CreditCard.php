<?php
/**
 * Created by PhpStorm.
 * User: nkalla
 * Date: 14/09/18
 * Time: 11:53
 */

namespace App\domain\model;


use Illuminate\Database\Eloquent\Model;

class CreditCard extends Model
{
    protected $table = 'creditcards';
    protected $fillable = ['b_id', 'card_number', 'holder', 'expiry_date', 'security_code', 'issuer', 'active', 'user', 'created_at', 'updated_at'];

    public function __construct($b_id = null, $card_number = null, $holder = null, $expiry_date = null, $security_code = null,
                                $issuer = null, $active = null, User $user = null,  array $attributes = [])
    {
        parent::__construct($attributes);
        $this->b_id = $b_id;
        $this->card_number = $card_number;
        $this->holder = $holder;
        $this->expiry_date = $expiry_date;
        $this->security_code = $security_code;
        $this->issuer = $issuer;
        $this->active = $active;
        $this->user = json_encode($user, JSON_UNESCAPED_SLASHES);
    }

}