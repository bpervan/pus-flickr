<?php
/**
 * Created by PhpStorm.
 * User: Branimir
 * Date: 23.05.14.
 * Time: 00:01
 */

class Comment {

    public $userId;
    public $imageId;
    public $comment;

    public function __construct($userId, $imageId, $comment){
        $this->userId = $userId;
        $this->imageId = $imageId;
        $this->comment = $comment;
    }

    public function __toString(){
        return "Korisnik " . $this->userId . " pise: " . $this->comment;
    }
} 