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
    protected $table = 'payments';
    protected $fillable = ['b_id', 'method', 'price', 'service', 'card', 'user', 'status', 'tries', 'created_at', 'updated_at'];

    public function __construct($b_id = null, array $method = null, array $price = null, array $service = null, array $card = null,
                                array $user = null, $status = null, $tries = null, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->b_id = $b_id;
        $this->method = json_encode($method,JSON_UNESCAPED_SLASHES);
        $this->price = json_encode($price,JSON_UNESCAPED_SLASHES);
        $this->service = json_encode($service,JSON_UNESCAPED_SLASHES);
        $this->card = json_encode($card,JSON_UNESCAPED_SLASHES);
        $this->user = json_encode($user,JSON_UNESCAPED_SLASHES);
        $this->status = $status;
        $this->tries = $tries;
    }
}