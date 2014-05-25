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
 * Time: 01:17
 */
if(isset($_GET['action'])){
    switch($_GET['action']){
        case "doLogout":
            session_destroy();
            $_SESSION = array();
            break;
        case "doLike":
            $db->insertLike($_GET['imageId'], $_SESSION['userId']);
            break;
        case "doRemoveLike":
            $db->removeLike($_GET['imageId'], $_SESSION['userId']);
            break;
        case "doPostComment":
            $db->insertComment(
                new Comment(
                    $_SESSION['userId'],
                    $_GET['imageId'],
                    $_GET['comment'],
                    null
                )
            );
            break;

        case "doDeleteComment":
            $db->deleteComment($_GET['commentId']);
                break;

        case "doTagFriend":
            $db->insertTag($_GET['imageId'], $_GET['userId']);
            break;

        case "doRemoveTag":
            $db->removeTag($_GET['imageId'], $_GET['userId']);
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
                    $liked = $db->hasUserLike($_GET['imageId'], $_SESSION['userId']);
                    $htmlEngine->printUserMainMenu($_SESSION['name'], $_GET['imageId'], $liked, $_SESSION['userId']);
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
            if(isset($_GET['imageId'])){
                $image = $db->getImage($_GET['imageId']);
                echo "<img class=\"img-polaroid\" src=\"images/{$image->url}\" alt=\"{$image->description}\" />";

                $likesArray = $db->getLikes($_GET['imageId']);
                if($likesArray != null){
                    echo "<p class=\"text-left navbar-text\">Lajkovi:";
                    echo "<ul>";
                    $count = count($likesArray);
                    for($i = 0; $i < $count; ++$i){
                        echo "<li>";
                        echo "<a href=\"showuser.php?userId={$likesArray[$i]->userId}\">{$likesArray[$i]->name} {$likesArray[$i]->surname}</a>";
                        echo "</li>";
                    }
                    echo "</ul>";
                    echo "</p>";
                }

                //Komentar može obrisati autor komentara ili vlasnik slike

                $commentsArray = $db->getComments($_GET['imageId']);
                if($commentsArray != null){
                    echo "<p class=\"text-left navbar-text\">Komentari:";
                    echo "<ul>";
                    $count = count($commentsArray);
                    for($i = 0; $i < $count; ++$i){
                        echo "<li>";
                        if(($_SESSION['userId'] == $commentsArray[$i]->userId) || ($_SESSION['userId'] == $image->userId)){
                            echo "<a href=\"showimage.php?action=doDeleteComment&imageId={$_GET['imageId']}&commentId={$commentsArray[$i]->commentId}\">{$commentsArray[$i]} - OBRIŠI</a>";
                        } else {
                            echo $commentsArray[$i];
                        }

                        echo "</li>";
                    }
                    echo "</ul>";
                    echo "</p>";
                }
                $htmlEngine->printCommentForm($_GET['imageId']);

                //Korisnik može odtagirati samog sebe, a vlasnik slike bilo koga
                $taggedArray = $db->getTags($_GET['imageId']);
                if($taggedArray != null){
                    echo "<p class=\"text-left navbar-text\">Tagirani korisnici:</p>";
                    echo "<ul>";
                    $count = count($taggedArray);
                    for($i = 0; $i < $count; ++$i){
                        echo "<li>";
                        //Korisnik može odtagirati samog sebe, a vlasnik slike bilo koga
                        if(($_SESSION['userId'] == $taggedArray[$i]->userId) || ($_SESSION['userId'] == $image->userId)){
                            echo "<a href=\"showimage.php?action=doRemoveTag&imageId={$_GET['imageId']}&userId={$taggedArray[$i]->userId}\">{$taggedArray[$i]->name} {$taggedArray[$i]->surname} - ODTAGIRAJ</a>";
                        } else {
                            echo "{$taggedArray[$i]->name} {$taggedArray[$i]->surname}";
                        }
                        echo "</li>";
                    }
                    echo "</ul>";
                }




                $usersArray = $db->getFriends($_SESSION['userId']);
                //Dakle, imam ovdje polje mojih prijatelja i polje već tagiranih korisnika
                //Sad treba proći kroz polje mojih prijatelja i u $offerTags dodati sve one prijatelje koji nisu u polju tagiranih korisnika
                $offerTags = array();
                $countUsers = count($usersArray);
                if($taggedArray == null){
                    $offerTags = $usersArray;
                } else {
                    for($i = 0; $i < $countUsers; ++$i){
                        if(!in_array($usersArray[$i], $taggedArray)){
                            array_push($offerTags, $usersArray[$i]);
                        }
                    }
                }

                //Korisnik mora moći tagirati sebe:
                if(!$db->isUserTagged($_GET['imageId'], $_SESSION['userId'])){
                    array_push($offerTags, User::constructCurrentUser());
                }

                if(count($offerTags) != 0){
                    echo "<p class=\"text-left navbar-text\">Tagiraj korisnika:</p>";
                    echo "<ul>";
                    $count = count($offerTags);
                    for($i = 0; $i < $count; ++$i){
                        echo "<li>";
                        echo "<a href=\"showimage.php?action=doTagFriend&imageId={$_GET['imageId']}&userId={$offerTags[$i]->userId}\">{$offerTags[$i]->name} {$offerTags[$i]->surname} - TAGIRAJ</a>";
                        echo "</li>";
                    }
                    echo "</ul>";
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