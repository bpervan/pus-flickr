<?php
/**
 * Created by PhpStorm.
 * User: Branimir
 * Date: 15.05.14.
 * Time: 17:02
 */

class User {

    public static $codeLoggedIn = 1;
    public static $codeLoggedOut = -1;

    public $userId;
    public $name;
    public $surname;
    public $email;
    public $username;

    public function __construct($userId, $name, $surname, $email, $username){
        $this->username = $username;
        $this->name = $name;
        $this->surname = $surname;
        $this->email = $email;
        $this->userId = $userId;
    }

    public static $ERROR_WRONG_USERNAME = -2;
    public static $ERROR_WRONG_PASSWORD = -3;

    public $error = array();
}