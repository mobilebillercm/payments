<?php
/**
 * Created by PhpStorm.
 * User: nkalla
 * Date: 14/09/18
 * Time: 12:12
 */

namespace App\domain\model;


class User
{
    public $firstname, $lastname, $b_id, $email;

    public function __construct($b_id, $firstname, $lastname, $email)
    {
        $this->b_id = $b_id;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->email = $email;
    }
}