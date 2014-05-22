<?php
include("Codes.php");
include("DBHandler.php");
/**
 * Created by PhpStorm.
 * User: Branimir
 * Date: 15.05.14.
 * Time: 19:32
 */
session_start();
if(isset($_SESSION['userLoggedIn'])){
    if($_SESSION['userLoggedIn'] == User::$codeLoggedIn){
        header("Location: index.php");
    }
}

if(isset($_POST['username']) && isset($_POST['password'])){
    $dbHandler = new DBHandler();
    $user = $dbHandler->checkUser($_POST['username'], sha1($_POST['password']));
    if($user != null){
        $_SESSION['userLoggedIn'] = User::$codeLoggedIn;

        $_SESSION['username'] = $user->username;
        $_SESSION['name'] = $user->name;
        $_SESSION['surname'] = $user->surname;
        $_SESSION['email'] = $user->email;
        $_SESSION['userId'] = $user->userId;
        header("Location: index.php");
    } else {
        $_SESSION['userLoggedIn'] = User::$codeLoggedOut;
        $_SESSION['errorCode'] = User::$ERROR_WRONG_PASSWORD;
        header("Location: index.php");
    }
}