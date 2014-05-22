<?php
include("HTMLEngine.php");
include("UploadEngine.php");
session_start();
$htmlEngine = new HTMLEngine();
/**
 * Created by PhpStorm.
 * User: Branimir
 * Date: 21.05.14.
 * Time: 15:23
 */
if(isset($_GET['action'])){
    switch($_GET['action']){
        case "doLogout":
            session_destroy();
            $_SESSION = array();
            break;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>PUS Flickr</title>
    <link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap.css" />
</head>

<body>
<div class="container">
    <h1><a href="#">PUS Flickr</a></h1>
    <div class="navbar">
        <div class="navbar-inner">
            <div class="container">
                <ul class="nav">
                    <li class="active"><a href="#">Početna</a></li>
                    <li><a href="#">Prijavi se</a></li>
                    <li><a href="#">Registracija</a></li>
                    <li><a href="#">Pretraži korisnike</a></li>

                    <li><a href="#">Slike</a></li>
                    <li><a href="#">Postavi novu sliku</a></li>
                    <li><a href="#">Moji prijatelji</a></li>

                    <li><a href="#">Odjavi se</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="span3">
            <?php
            if(isset($_SESSION['userLoggedIn'])){
                if($_SESSION['userLoggedIn'] == User::$codeLoggedIn){
                    $htmlEngine->printUserMainMenu($_SESSION['name']);
                } else {
                    $htmlEngine->printLoginForm();
                }
            } else {
                $htmlEngine->printLoginForm();
            }
            ?>
        </div>
        <div class="span9">
            <?php
            if(isset($_POST['name'])){
                $uploadEngine = new UploadEngine();
                try{
                    $uploadEngine->handleImageUpload();
                } catch (RuntimeException $e){
                    $e->getMessage();
                }


                /*xdebug_var_dump($_FILES);
                xdebug_var_dump($_POST);*/
            } else {
                $htmlEngine->printUploadForm();
            }

            ?>
        </div>
    </div>
</div>
<script src="http://code.jquery.com/jquery-1.10.1.min.js" />
<script src="bootstrap/js/bootstrap.js" />
</body>
</html>