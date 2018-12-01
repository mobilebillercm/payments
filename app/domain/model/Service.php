<?php
/**
 * Created by PhpStorm.
 * User: nkalla
 * Date: 14/09/18
 * Time: 14:40
 */

namespace App\domain\model;


class Service
{
    public $b_id, $name, $description;

    public function __construct($b_id, $name, $description)
    {
        $this->b_id = $b_id;
        $this->name = $name;
        $this->description = $description;
    }
}