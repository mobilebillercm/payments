<?php
/**
 * Created by PhpStorm.
 * User: nkalla
 * Date: 14/09/18
 * Time: 14:36
 */

namespace App\domain\model;


class Duration
{
    public $timeUnit, $value;

    public function __construct($timeUnit, $value)
    {
        $this->timeUnit = $timeUnit;
        $this->value = $value;
    }
}