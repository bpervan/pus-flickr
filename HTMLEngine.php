<?php
/**
 * Created by PhpStorm.
 * User: Branimir
 * Date: 15.05.14.
 * Time: 17:07
 */

class HTMLEngine {

    public function __construct(){}

    public function printHtmlHeader(){
        echo "<!DOCTYPE html>";
        echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" lang=\"en\">";
        echo "<head>";
        echo "<body>";
    }

    public function printHtmlFooter(){
        echo "</body>";
        echo "</html>";
    }



    public function printLoginForm(){
        echo "
            <form name=\"loginForm\" method=\"post\" action=\"login.php\">
                <p class=\"text-info\">Korisničko ime:
                    <input class=\"input-large\" name=\"username\" type=\"text\" /><br />
                </p>
                <p class=\"text-info\">Lozinka:
                    <input class=\"input-large\" name=\"password\" type=\"password\" /><br />
                </p>
                    <input class=\"btn btn-large btn-success\" value=\"Prijavi se\" type=\"submit\" /><br />
            </form>
        ";
    }

    public function printRegisterForm(){
        echo "
            <form name=\"registerForm\" method=\"post\" action=\"register.php\">
                <p class=\"text-info\">Korisničko ime:
                    <input class=\"input-large\" name=\"username\" type=\"text\" /><br />
                </p>
                <p class=\"text-info\">Lozinka:
                    <input class=\"input-large\" name=\"password\" type=\"password\" /><br />
                </p>
                <p class=\"text-info\">Ime:
                    <input class=\"input-large\" name=\"name\" type=\"text\" /><br />
                </p>
                <p class=\"text-info\">Prezime:
                    <input class=\"input-large\" name=\"surname\" type=\"text\" /><br />
                </p>
                <p class=\"text-info\">E - mail:
                    <input class=\"input-large\" name=\"email\" type=\"text\" /><br />
                </p>
                    <input class=\"btn btn-large btn-success\" value=\"Registriraj se\" type=\"submit\" /><br />
            </form>
        ";
    }

    public function printUploadForm(){
        echo "
            <form method=\"post\" action=\"uploadimage.php\" enctype=\"multipart/form-data\">
                <p class=\"text-info\">Naziv slike:<br />
                    <input class=\"input-large\" name=\"name\" type=\"text\" /><br />
                </p>
                <p class=\"text-info\">Opis slike:<br />
                    <input class=\"input-large\" name=\"description\" type=\"text\" /><br />
                </p>
                <p class=\"text-info\">Datoteka:<br />
                    <input class=\"input-large\" type=\"file\" name=\"image\" /><br />
                </p>
                <p class=\"text-info\">Privatno?:<br />
                    <input class=\"checkbox\" type=\"checkbox\" name=\"private\" /><br />
                </p>
                <input class=\"btn btn-large btn-success\" value=\"Pošalji sliku\" type=\"submit\" /><br />
            </form>
        ";
    }

    public function printUserMainMenu($name, $imageId = null, $liked = null, $userId = null){
        echo"
            <ul class=\"nav nav-list\">
                <li class=\"nav-header\">Trenutno prijavljen: {$name}</li>
                <li><a href=\"userfilter.php\">Pregledaj popis korisnika</a></li>
                <li><a href=\"imagefilter.php?action=getPublicPhotos\">Pregledaj javno dostupne slike</a></li>
                <li><a href=\"search.php\">Pretraga korisnika</a></li>
                <li><a href=\"showuser.php?userId={$userId}\">Pogledaj svoj profil</a></li>
            ";
        if($imageId != null){
            if(true == $liked){
                echo "<li><a href=\"showimage.php?action=doRemoveLike&imageId={$imageId}\">Makni like</a></li>";
            } else {
                echo "<li><a href=\"showimage.php?action=doLike&imageId={$imageId}\">Lajkaj sliku</a></li>";
            }
            echo "<li><a href=\"#\">Označi prijatelja na slici</a></li>";
        }

        echo "
                <li><a href=\"uploadimage.php\">Dodaj sliku</a></li>
                <li><a href=\"index.php?action=doLogout\">Odjavi se</a></li>
            </ul>
        ";
    }

    public function printUserUserProfileMenu($name, $userId, $friends){
        echo"
            <ul class=\"nav nav-list\">
                <li class=\"nav-header\">Trenutno prijavljen: {$name}</li>
                <li><a href=\"userfilter.php\">Pregledaj popis korisnika</a></li>
                <li><a href=\"imagefilter.php?action=getPublicPhotos\">Pregledaj javno dostupne slike</a></li>
                <li><a href=\"search.php\">Pretraga korisnika</a></li>
            ";

        if($userId != $_SESSION['userId']){
            if(User::$FRIENDSHIP_FRIENDS == $friends){
                echo "<li>Frendovi</li>";
            } else if(User::$FRIENDSHIP_NOT_FRIENDS == $friends) {
                echo "<li><a href=\"showuser.php?action=doRequestFriendship&userId={$userId}\">Zatraži prijateljstvo</a></li>";
            } else if (User::$FRIENDSHIP_REQUESTED == $friends){
                echo "<li>Friendship requested</li>";
            }
        }

        echo "
                <li><a href=\"uploadimage.php\">Dodaj sliku</a></li>
                <li><a href=\"index.php?action=doLogout\">Odjavi se</a></li>
            </ul>
        ";
    }

    public function printTopMenu(){
        echo "
        <ul class=\"nav\">
            <li><a href=\"index.php\">Početna</a></li>
            <li><a href=\"search.php\">Pretraga korisnika</a></li>
            <li><a href=\"register.php\">Registracija</a></li>
            <li><a href=\"index.php?action=doLogout\">Odjavi se</a></li>
        </ul>
        ";
    }

    public function printCommentForm($imageId){
        echo "
        <form name=\"commentForm\" method=\"get\" action=\"showimage.php\">
            <p class=\"text-info\">Komentar:
                <textarea class=\"input-xlarge\" name=\"comment\"></textarea>
            </p>
            <input type=\"hidden\" name=\"imageId\" value=\"{$imageId}\" />
            <input type=\"hidden\" name=\"action\" value=\"doPostComment\" />
            <input class=\"btn btn-large btn-success\" value=\"Pošalji komentar\" type=\"submit\" /><br />
        </form>
        ";
    }

    public function printSearchForm($returnAddress = null){
        echo "
            <form name=\"searchForm\" method=\"get\" action=\"search.php\">
                <p class=\"text-info\">Ime:<br />
                    <input class=\"input-large\" name=\"name\" type=\"text\" /><br />
                </p>
                <p class=\"text-info\">Prezime:<br />
                    <input class=\"input-large\" name=\"surname\" type=\"text\" /><br />
                </p>
                <input type=\"hidden\" name=\"action\" value=\"doSearch\" />

            ";
        if($returnAddress != null){
            echo "<input type=\"hidden\" name=\"\" value=\"{$returnAddress}\" />";
        }
        echo "<input class=\"btn btn-large btn-success\" value=\"Traži\" type=\"submit\" /><br />
            </form>
        ";
    }
}