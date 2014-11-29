<?php
/**
 * Created by PhpStorm.
 * User: Branimir
 * Date: 25.11.2014.
 * Time: 0:03
 */

class ResponseBuilder {
    private $response;

    public function __construct(){
        $this->response = array();
    }

    public function __destruct(){

    }

    public function build(){
        return $this->response;
    }

    public function setStatus($statusCode = 200, $statusMessage = "Success"){
        $this->response['StatusCode'] = $statusCode;
        $this->response['StatusMessage'] = $statusMessage;
    }

    public function putPhoto($id, $name = "", $description = "", $url = ""){

    }

    public function putUser($userId, $username, $name, $surname, $email){

    }
}