<?php
/**
 * Created by PhpStorm.
 * User: nkalla
 * Date: 14/09/18
 * Time: 19:06
 */

namespace App\domain\model;


class Api
{
    public $paymentUrl;

    public function __construct($paymentUrl)
    {
        $this->paymentUrl = $paymentUrl;
    }
}