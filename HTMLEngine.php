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
                <p class=\"text-info\">Korisničko ime:
                    <input class=\"input-large\" name=\"password\" type=\"password\" /><br />
                </p>
                    <input class=\"btn btn-large btn-success\" value=\"Prijavi se\" type=\"submit\" /><br />
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
                <input class=\"btn btn-large btn-success\" value=\"Pošalji sliku\" type=\"submit\" /><br />
            </form>
        ";
    }

    public function printUserMainMenu($name){
        echo"
            <ul class=\"nav nav-list\">
                <li class=\"nav-header\">Trenutno prijavljen: {$name}</li>
                <li><a href=\"#\">Pregledaj popis prijatelja</a></li>
                <li><a href=\"#\">Pregledaj svoje slike</a></li>
                <li><a href=\"#\">Pregledaj slike prijatelja</a></li>
                <li><a href=\"#\">Pregledaj javno dostupne slike</a></li>
                <li><a href=\"#\">Lajkaj sliku</a></li>
                <li><a href=\"#\">Označi prijatelja na slici</a></li>
                <li><a href=\"uploadimage.php\">Dodaj sliku</a></li>
                <li><a href=\"index.php?action=doLogout\">Odjavi se</a></li>
            </ul>
        ";
    }

    public function printTopMenu(){
        echo "
        <ul class=\"nav\">
            <li><a href=\"index.php\">Početna</a></li>
            <li><a href=\"register.php\">Registracija</a></li>
            <li><a href=\"index.php?action=doLogout\">Odjavi se</a></li>
        </ul>
        ";
    }
}