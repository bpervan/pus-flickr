<?php
/**
 * Created by PhpStorm.
 * User: Branimir
 * Date: 24.11.2014.
 * Time: 21:21
 */

include('ResponseBuilder.php');
include('../DBHandler.php');
include('php-WebHDFS/WebHDFS.php');

class Stats{
    private $method;
    private $methodVars;
    private $responseBuilder;
    private $dbHandler;

    private $logFile;
    private $urlLog;
    private $browserLog;

    private $hdfs;

    public function __construct(){
        $this->responseBuilder = new ResponseBuilder();
        $this->dbHandler = new DBHandler();

        //$this->logFile = fopen("access.log.txt", 'r');
        /*$this->urlLog = fopen("url.log.txt", 'r');
        $this->browserLog = fopen("browser.log.txt", 'r');*/

        $this->hdfs = new WebHDFS('localhost', '50070', 'root');
        $this->urlLog = $this->hdfs->open("outURL/part-00000");
        $this->browserLog = $this->hdfs->open("outBrowsers/part-00000");


        $this->parseRequest();
    }

    public function __destruct(){
        $this->methodVars = array();
        //fclose($this->logFile);
        /*fclose($this->urlLog);
        fclose($this->browserLog);*/
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
        $data = array();

        if(isset($_GET['action'])){
            switch($_GET['action']){
                case 'url';
                    //Split by \n
                    $splitByLF = preg_split('/\n+/', $this->urlLog);

                    //Split by \t
                    $countVar = count($splitByLF);
                    for($i = 0; $i < $countVar - 1; ++$i){
                        $splitByT = preg_split('/\s+/', $splitByLF[$i]);
                        $data[$splitByT[0]] = (int) $splitByT[1];
                    }

                    http_response_code(200);
                    $response = array("StatusCode" => 200, "StatusMessage" => "OK", "Data" => $data);
                    break;

                case 'browser':
                    //Split by \n
                    $splitByLF = preg_split('/\n+/', $this->browserLog);

                    //Split by \t
                    $countVar = count($splitByLF);
                    for($i = 0; $i < $countVar - 1; ++$i){
                        $splitByT = preg_split('/\s+/', $splitByLF[$i]);
                        $data[$splitByT[0]] = (int) $splitByT[1];
                    }

                    http_response_code(200);
                    $response = array("StatusCode" => 200, "StatusMessage" => "OK", "Data" => $data);
                    break;

                default:
                    http_response_code(400);
                    $response = array("StatusCode" => 400, "StatusMessage" => "Bad Request");
                    break;
            }
        } else {
            http_response_code(400);
            $response = array("StatusCode" => 400, "StatusMessage" => "Bad Request");
        }

        header("Content-Type: application/json");
        echo json_encode($response);
    }

    private function doPost(){

    }

    private function doPut(){

    }

    private function doDelete(){

    }
}

/** Run the engine */
$stats = new Stats();