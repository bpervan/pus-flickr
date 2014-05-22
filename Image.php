<?php
/**
 * Created by PhpStorm.
 * User: Branimir
 * Date: 22.05.14.
 * Time: 01:12
 */

class Image {

    public $pictureId;
    public $userId;
    public $likes;
    public $url;
    public $name;
    public $description;
    public $privacy;

    public function __construct($pictureId, $userId, $likes, $url, $name, $description, $privacy){
        $this->pictureId = $pictureId;
        $this->userId = $userId;
        $this->likes = $likes;
        $this->url = $url;
        $this->name = $name;
        $this->description = $description;
        $this->privacy = $privacy;
    }
} 