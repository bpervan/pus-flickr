<?php
include("HTMLEngine.php");
include("DBHandler.php");
session_start();
$htmlEngine = new HTMLEngine();
$db = new DBHandler();
/**
 * Created by PhpStorm.
 * User: Branimir
 * Date: 25.05.14.
 * Time: 17:18
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
                <?php $htmlEngine->printTopMenu(); ?>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="span3">
            <?php
            if(isset($_SESSION['userLoggedIn'])){
                if($_SESSION['userLoggedIn'] == User::$codeLoggedIn){
                    $htmlEngine->printUserMainMenu($_SESSION['name'], null, null, $_SESSION['userId']);
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
            $usersArray = $db->getSystemUsers();
            if($usersArray != null){
                echo "<p class=\"text-left navbar-text\">Svi korisnici sustava:";
                echo "<ul>";
                $count = count($usersArray);
                for($i = 0; $i < $count; ++$i){
                    echo "<li>";
                    echo "<a href=\"showuser.php?userId={$usersArray[$i]->userId}\">{$usersArray[$i]}</a>";
                    echo "</li>";
                }
                echo "</ul>";
                echo "</p>";
            }
            ?>
        </div>
    </div>
</div>
<script src="http://code.jquery.com/jquery-1.10.1.min.js" />
<script src="bootstrap/js/bootstrap.js" />
</body>
</html>
