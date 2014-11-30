<?php
/**
 * Created by PhpStorm.
 * User: Branimir
 * Date: 24.11.2014.
 * Time: 21:21
 */

include('ResponseBuilder.php');
include('../UploadEngine.php');

class Photos{
    private $method;
    private $methodVars;
    private $responseBuilder;
    private $dbHandler;
    private $uploadEngine;

    private $logFile;

    public function __construct(){
        $this->responseBuilder = new ResponseBuilder();
        $this->dbHandler = new DBHandler();
        $this->uploadEngine = new UploadEngine();

        $this->logFile = fopen("log.txt", 'a');

        $this->parseRequest();
    }

    public function __destruct(){
        $this->methodVars = array();
    }

    private function parseRequest(){
        $retVal = array();
        $this->method = $_SERVER['REQUEST_METHOD'];
        switch($_SERVER['REQUEST_METHOD']){
            case 'GET':
                $retVal = $_GET;
                $this->doGet();
                break;

            case 'POST':
                $retVal = $_POST;
                $this->doPost();
                break;

            case 'PUT':
                parse_str(file_get_contents("php://input"), $retVal);
                $this->methodVars = $retVal;
                $this->doPut();
                break;
            case 'DELETE':
                parse_str(file_get_contents("php://input"), $retVal);
                $this->methodVars = $retVal;
                $this->doDelete();
                break;
        }
    }

    /**
     * GET /photos -> Dohvaćaju se sve slike svih korisnika
     * GET /photos/photoId -> Dohvaća se pojedinačna slika
     */
    private function doGet(){
        //Ako je postavljen photoId, dovaćamo pojedinačnu sliku
        if(isset($_GET['photoId'])){
            $photo = $this->dbHandler->getPicture($_GET['photoId']);
            $response = array("StatusCode" => 200, "StatusMessage" => "OK", "Photo" => $photo);
        } else {
            //Sad dohvaćamo sve slike
            $photos = $this->dbHandler->getAllPictures();
            $response = array("StatusCode" => 200, "StatusMessage" => "OK", "Photos" => $photos);
        }
        http_response_code(200);
        header("Content-Type: application/json");
        echo json_encode($response);
    }

    /**
     * POST /photos -> dodaje se nova slika (obavezni parametri username i pass korisnika koji šalju sliku)
     */
    private function doPost(){
        if(isset($_POST['username']) && isset($_POST['password'])){
            $user = $this->dbHandler->checkUser($_POST['username'], sha1($_POST['password']));
            if(!is_null($user)){
                $_POST['userId'] = $user->userId;
                $this->uploadEngine->handleImageUpload();
                http_response_code(201);
                $response = array("StatusCode" => 201, "StatusMessage" => "Created");
            } else {
                http_response_code(401);
                $response = array("StatusCode" => 401, "StatusMessage" => "Unauthorized");
            }
        } else {
            http_response_code(401);
            $response = array("StatusCode" => 401, "StatusMessage" => "Unauthorized");
        }
        header("Content-Type: application/json");
        echo json_encode($response);
    }

    /** PUT /photos/photoId -> kreira se nova slika sa photoId ili se apdejta slika sa photoId */
    private function doPut(){
        if(isset($this->methodVars['photoId']) && isset($this->methodVars['username']) && isset($this->methodVars['password'])){
            $user = $this->dbHandler->checkUser($this->methodVars['username'], sha1($this->methodVars['password']));
            if(!is_null($user)){
                //user postoji,
                $photo = $this->dbHandler->getPicture($this->methodVars['photoId']);
                //xdebug_var_dump($photo);
                if(is_null($photo)){
                    //kreiramo sliku
                    $this->dbHandler->insertImageToDatabase(
                        $this->methodVars['photoId'],
                        $user->userId,
                        $this->methodVars['url'],
                        $this->methodVars['name'],
                        $this->methodVars['description']);
                    //test
                    http_response_code(201);
                    $response = array("StatusCode" => 201, "StatusMessage" => "Created", $this->methodVars);
                } else {
                    //apdejtamo postojecu, treba provjeriti je li ovaj user vlasnik te slike
                    if($user->userId == $photo->userId){
                        //user je vlasnik slike, slijedi update
                        if(isset($this->methodVars['name'])){
                            $photo->name = $this->methodVars['name'];
                        }
                        if(isset($this->methodVars['description'])){
                            $photo->description = $this->methodVars['description'];
                        }
                        if(isset($this->methodVars['url'])){
                            $photo->url = $this->methodVars['url'];
                        }
                        $this->dbHandler->updatePicture($photo);
                        http_response_code(202);
                        $response = array("StatusCode" => 202, "StatusMessage" => "Accepted", $this->methodVars);
                    } else {
                        http_response_code(401);
                        $response = array("StatusCode" => 401, "StatusMessage" => "Unauthorized", $this->methodVars);
                    }
                }
            } else {
                //user ne postoji, unauthorized
                http_response_code(401);
                $response = array("StatusCode" => 401, "StatusMessage" => "Unauthorized", $this->methodVars);
            }
        } else {
            http_response_code(400);
            $response = array("StatusCode" => 400, "StatusMessage" => "Bad Request", $this->methodVars);
        }
        header("Content-Type: application/json");
        echo json_encode($response);
    }

    /** DELETE /photos/photoId -> briše se slika sa photoId, potrebno poslati username i password vlasnika fotke */
    private function doDelete(){
        if(isset($this->methodVars['username']) && isset($this->methodVars['password']) && isset($this->methodVars['photoId'])){
            $user = $this->dbHandler->checkUser($this->methodVars['username'], sha1($this->methodVars['password']));
            if(is_null($user)){
                http_response_code(401);
                $response = array("StatusCode" => 401, "StatusMessage" => "Unauthorized", $this->methodVars);
            } else {
                //user postoji, samo još treba provjeriti je li vlasnik slike
                if(0 == $this->dbHandler->deleteUserPicture($this->methodVars['photoId'], $user->userId)){
                    http_response_code(202);
                    $response = array("StatusCode" => 202, "StatusMessage" => "Accepted", $this->methodVars);
                } else {
                    http_response_code(401);
                    $response = array("StatusCode" => 401, "StatusMessage" => "Unauthorized", $this->methodVars);
                }
            }
        } else {
            http_response_code(400);
            $response = array("StatusCode" => 400, "StatusMessage" => "Bad Request", $this->methodVars);
        }

        $log = "/photos/{$this->methodVars['photoId']}\t{$_SERVER['HTTP_USER_AGENT']}\n\r";
        fwrite($this->logFile, $log);
        header("Content-Type: application/json");
        echo json_encode($response);
    }
}

/** Run the engine */
$photos = new Photos();