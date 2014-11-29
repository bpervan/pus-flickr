<?php
/**
 * Created by PhpStorm.
 * User: Branimir
 * Date: 24.11.2014.
 * Time: 21:20
 */

//include('../DBHandler.php');
include('ResponseBuilder.php');
include('../UploadEngine.php');

class Users{

    private $method;
    private $methodVars;
    private $responseBuilder;
    private $dbHandler;
    private $uploadEngine;

    public function __construct(){
        $this->responseBuilder = new ResponseBuilder();
        $this->dbHandler = new DBHandler();
        $this->uploadEngine = new UploadEngine();

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
     * GET /users -> Dohvaća se lista svih korisnika
     * GET /users/id (users.php?id=id) -> Dohvaća se korisnik s ID-jem id
     * GET /users/id/images (users.php?id=id&action=photos -> Dohvaćaju se sve fotografije korisnika
     */
    private function doGet(){
        if(isset($_GET['userId'])){
            //Ako je postavljen 'action' i ako je == 'photos', dohvati sve fotke korisnika
            if(isset($_GET['action'])){
                if($_GET['action'] == "photos"){
                    $photos = $this->dbHandler->getPublicImagesForUser($_GET['userId']);
                    $response = array("StatusCode" => 200, "StatusMessage" => "OK", "UserPhotos" => $photos);
                    header("Content-Type: application/json");
                    echo json_encode($response);
                }
            } else {
                //Ako nije postavljen 'action', ispisujemo podatke o određenom korisniku
                $user = $this->dbHandler->getUser($_GET['userId']);
                $response = array("StatusCode" => 200, "StatusMessage" => "OK", "User" => $user);
                header("Content-Type: application/json");
                echo json_encode($response);
            }
        } else {
            //ako nije postavljen 'userId', onda idemo na ispis svih usera u sustavu
            $users = $this->dbHandler->getSystemUsers();
            $response = array("StatusCode" => 200, "StatusMessage" => "OK", "Users" => $users);
            header("Content-Type: application/json");
            echo json_encode($response);
        }
    }

    /**
     * POST /users -> DOdaje se novi korisnik
     * POST /users/id/images -> Dodaje se nova slika korisnika određenog id-jem
     */
    private function doPost(){
        if(isset($_POST['userId'])){
            if(isset($_POST['action'])){
                if($_POST['action'] == "photos"){
                    /**
                     * 1. Provjeri credentialse (username i pass), ak se nemre prijavit, baci grešku (doviđorno)
                     * 2. Ako credentialsi valjaju, pozovi UploadEngine::handleImageUpload i postavi $_POST['userId']
                     * 3. Unutar tog upload engine - a izmijeni insertToDB funkciju da ubere userId is posta a ne sessiona
                     */
                    $user = $this->dbHandler->checkUser($_POST['username'], sha1($_POST['password']));
                    if(!is_null($user)){
                        $_POST['userId'] = $user->userId;
                        $this->uploadEngine->handleImageUpload();
                        $response = array("StatusCode" => 4487, "StatusMessage" => "Uploaded");
                    } else {
                        $response = array("StatusCode" => 65411, "StatusMessage" => "Not authorized");
                    }
                }
            }
        } else {
            if(isset($_POST['username']) && isset($_POST['password']) && isset($_POST['name']) && isset($_POST['surname'])){
                $userId = $this->dbHandler->getLastUserId() + 1;
                $this->dbHandler->insertUser(new User(
                    $userId,
                    $_POST['name'],
                    $_POST['surname'],
                    $_POST['email'],
                    $_POST['username']
                ), sha1($_POST['password']));

                $response = array("StatusCode" => 1234, "StatusMessage" => "Created");

            } else {
                $response = array("StatusCode" => 3214, "StatusMessage" => "Insufficient data");
            }
        }
        header("Content-Type: application/json");
        echo json_encode($response);
    }

    /** PUT /users/id -> Dodaje se novi korisnik s id-jem. ako id postoji, korisnik se update-a podacima iz requesta */
    private function doPut(){
        if(isset($this->methodVars['userId'])){
            $user = $this->dbHandler->getUser($this->methodVars['userId']);
            //User je NULL, treba ga stvoriti
            if(is_null($user)){
                if(isset($this->methodVars['username']) &&
                    isset($this->methodVars['password']) &&
                    isset($this->methodVars['name']) &&
                    isset($this->methodVars['surname'])) {
                    $userId = $this->methodVars['userId'];
                    $this->dbHandler->insertUser(new User(
                        $userId,
                        $this->methodVars['name'],
                        $this->methodVars['surname'],
                        $this->methodVars['email'],
                        $this->methodVars['username']
                    ), sha1($this->methodVars['password']));
                }
                $response = array("StatusCode" => 3214, "StatusMessage" => "Created", $this->methodVars);
            } else {
                //User postoji, radimo update sa poslanim podacima

                //trenutno ovdje postoji jedan krucijalan propust, svaki mujo može updateat kojeg god hoće usera xD
                //druga banana je što se radi update svih fieldova u bazi. trebalo bi složiti da se updateaju samo oni
                //koji su poslani u parametrima metode. i onda složiti nekakav UPDATE query builder
                if(isset($this->methodVars['username'])){
                    $user->username = $this->methodVars['username'];
                }
                if(isset($this->methodVars['name'])){
                    $user->name = $this->methodVars['name'];
                }
                if(isset($this->methodVars['surname'])){
                    $user->surname = $this->methodVars['surname'];
                }
                if(isset($this->methodVars['email'])){
                    $user->email = $this->methodVars['email'];
                }

                $this->dbHandler->updateUser($user, sha1($this->methodVars['password']));

                $response = array("StatusCode" => 217, "StatusMessage" => "Updated", $this->methodVars);
            }


        } else {
            $response = array("StatusCode" => 3214, "StatusMessage" => "PUT Insufficient data", $this->methodVars);
        }
        header("Content-Type: application/json");
        echo json_encode($response);
    }

    /** DELETE /users/id -> Briše se korisnik s id-jem */
    private function doDelete(){
        if(isset($this->methodVars['userId'])){
            $user = $this->dbHandler->checkUser($this->methodVars['username'], sha1($this->methodVars['password']));
            if(is_null($user)){
                $response = array("StatusCode" => 9874, "StatusMessage" => "Unauthorized", $this->methodVars);
            } else {
                $this->dbHandler->deleteUser($user);
                $response = array("StatusCode" => 1234, "StatusMessage" => "Deleted", $this->methodVars);
            }
        }
        header("Content-Type: application/json");
        echo json_encode($response);
    }
}

/** Run the engine:) */
$users = new Users();
