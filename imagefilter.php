<?php
include("HTMLEngine.php");
include("DBHandler.php");
session_start();
$htmlEngine = new HTMLEngine();
$db = new DBHandler();
/**
 * Created by PhpStorm.
 * User: Branimir
 * Date: 24.05.14.
 * Time: 00:59
 *
 * Case 1: Lista sve javno dostupne slike action=getPublicPhotos
 * Case 2: Lista sve javno dostupne slike nekog korisnika + njegove privatne ako je prijatelj s trenutno
 *         ulogiranim korisnikom
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
            <p class="text-center text-info btn-large">JAVNE SLIKE</p>
            <table class="table-bordered">
                <tr>
                    <?php
                    $publicImages = $db->getPublicImages();
                    $numberOfImages = count($publicImages);
                    for($i = 0; $i < $numberOfImages; ++$i){
                        echo "<td><a href=\"showimage.php?imageId={$publicImages[$i]->pictureId}\"><img class=\"img-polaroid\" src=\"images/thumbnails/{$publicImages[$i]->url}\" alt=\"{$publicImages[$i]->description}\" /></a></td>";
                        if(($i + 1) % 4 == 0 && ($i + 1) < $numberOfImages)
                            echo "</tr><tr>";
                    }
                    ?>
                </tr>
            </table>
        </div>
    </div>
</div>
<script src="http://code.jquery.com/jquery-1.10.1.min.js" />
<script src="bootstrap/js/bootstrap.js" />
</body>
</html>
