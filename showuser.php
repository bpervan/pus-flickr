<?php
include("HTMLEngine.php");
include("DBHandler.php");
session_start();
$htmlEngine = new HTMLEngine();
$db = new DBHandler();
/**
 * Created by PhpStorm.
 * User: Branimir
 * Date: 22.05.14.
 * Time: 21:50
 */
if(isset($_GET['action'])){
    switch($_GET['action']){
        case "doLogout":
            session_destroy();
            $_SESSION = array();
            break;
        case "doRequestFriendship":
            $db->makeFriendshipRequest($_SESSION['userId'], $_GET['userId']);
            break;
        case "doConfirmFriendship":
            $db->confirmFriend($_SESSION['userId'], $_GET['userId']);
            break;
        case "doDenyFriendship":

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
                    $htmlEngine->printUserUserProfileMenu(
                        $_SESSION['name'],
                        $_GET['userId'],
                        $db->getFriendshipStatus($_SESSION['userId'], $_GET['userId']));
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
            if(isset($_GET['userId'])){
                $user = $db->getUser($_GET['userId']);
                echo "
                <p class=\"text-center text-info\">Ime: {$user->name}</p>
                <p class=\"text-center text-info\">Prezime: {$user->surname}</p>
                <p class=\"text-center text-info\">E - mail: {$user->email}</p>
                <p class=\"text-center text-info\">Username: {$user->username}</p>
                <p class=\"text-center text-info\">User ID: {$user->userId}</p>
                ";

                if($_SESSION['userId'] == $_GET['userId']){
                    echo " <p class=\"text-center\">Ovo je moj profil!</p>";

                    $friendshipRequests = $db->getFriendshipRequests($_GET['userId']);
                    if($friendshipRequests != null){
                        echo " <p class=\"text-center\">Dostupni su zahtjevi za prijateljstvo</p>";
                        echo "<ul>";
                        $count = count($friendshipRequests);
                        for($i = 0; $i < $count; ++$i){
                            echo "<li>
                        {$friendshipRequests[$i]->name} {$friendshipRequests[$i]->surname}
                        <a href=\"showuser.php?action=doConfirmFriendship&userId={$friendshipRequests[$i]->userId}\">POTVRDI PRIJATELJSTVO
                        <a href=\"showuser.php?action=doDenyFriendship&userId={$friendshipRequests[$i]->userId}\">ODBIJ PRIJATELJSTVO</a>
                        </li>";
                        }
                        echo "</ul>";
                    }
                }
            }
            ?>
        </div>
    </div>
</div>
<script src="http://code.jquery.com/jquery-1.10.1.min.js" />
<script src="bootstrap/js/bootstrap.js" />
</body>
</html>