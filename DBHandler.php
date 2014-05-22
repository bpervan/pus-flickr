<?php
include("DBConst.php");
include("User.php");
include("Image.php");
/**
 * Created by PhpStorm.
 * User: Branimir
 * Date: 15.05.14.
 * Time: 21:46
 */

class DBHandler {

    private $connectionHandle;

    public function __construct(){
        $opt = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        );
        $this->connectionHandle = new PDO('mysql:host=localhost', DBUSER, DBPASSWORD, $opt);
    }

    public function __destruct(){
        $this->connectionHandle = null;
    }

    public function checkUser($username, $hashedpassword){
        $checkUserQuery = $this->connectionHandle->prepare("SELECT * FROM pusflickr.users where username = ? and password = ?");
        $checkUserQuery->bindParam(1, $username);
        $checkUserQuery->bindParam(2, $hashedpassword);
        if($checkUserQuery->execute()){
            if($row = $checkUserQuery->fetch()){
                $user = new User($row['userId'], $row['name'], $row['surname'], $row['email'], $row['username']);
                return $user;
            }
        }
        return null;
    }

    public function insertImageToDatabase($pictureId, $userId, $url, $name, $description){
        $insertQuery = $this->connectionHandle->prepare(
            "INSERT INTO pusflickr.pictures (pictureId, userId, url, name, description, privacy)
            VALUES (?,?,?,?,?,?)"
        );
        $privacy = 0;

        $insertQuery->bindParam(1, $pictureId);
        $insertQuery->bindParam(2, $userId);
        $insertQuery->bindParam(3, $url);
        $insertQuery->bindParam(4, $name);
        $insertQuery->bindParam(5, $description);
        $insertQuery->bindParam(6, $privacy);

        $insertQuery->execute();
    }

    public function getLastPictureId(){
        $fetchQuery = $this->connectionHandle->prepare("SELECT pictureId FROM pusflickr.pictures ORDER BY pictureId DESC LIMIT 1");
        if($fetchQuery->execute()){
            if($row = $fetchQuery->fetch()){
                return $row['pictureId'];
            } else {
                return 1;
            }
        }
        return 1;
    }

    public function getImagesForIndex(){
        $retval = array();

        $fetchQuery = $this->connectionHandle->prepare("SELECT * FROM pusflickr.pictures WHERE privacy = 0 LIMIT 20");
        if($fetchQuery->execute()){
            while($row = $fetchQuery->fetch()){
                array_push($retval,
                    new Image(
                        $row['pictureId'],
                        $row['userId'],
                        $row['likes'],
                        $row['url'],
                        $row['name'],
                        $row['description'],
                        $row['privacy']
                ));
            }
        }
        return $retval;
    }
} 