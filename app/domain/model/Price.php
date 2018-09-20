<?php
/**
 * Created by PhpStorm.
 * User: nkalla
 * Date: 14/09/18
 * Time: 14:31
 */

namespace App\domain\model;


class Price
{
    public $amount, $duration;

    public function __construct(Amount $amount, Duration $duration)
    {
        $this->amount = $amount;
        $this->duration = $duration;
    }

}