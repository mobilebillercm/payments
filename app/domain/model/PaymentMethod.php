<?php
/**
 * Created by PhpStorm.
 * User: nkalla
 * Date: 14/09/18
 * Time: 10:15
 */

namespace App\domain\model;


use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $table = 'paymentmethods';
    protected $fillable = ['b_id', 'name', 'description', 'provider', 'icon', 'active', 'created_by', 'created_at', 'updated_at'];

    public function __construct($b_id = null, $name = null, $description = null, $provider = null, $icon = null, $active = null, $created_by = null, array $attributes = [])
    {
        parent::__construct($attributes);
        $this->b_id = $b_id;
        $this->name = $name;
        $this->description = $description;
        $this->provider = $provider;
        $this->icon = $icon;
        $this->active = $active;
        $this->created_by = $created_by;
    }

}