<?php
/**
 * Created by PhpStorm.
 * User: nkalla
 * Date: 14/09/18
 * Time: 14:32
 */

namespace App\domain\model;


class Amount
{
    public $curency, $value;

    public function __construct($curency, $value)
    {
        $this->curency = $curency;
        $this->value = $value;
    }
}