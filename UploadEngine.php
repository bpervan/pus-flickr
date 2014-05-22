<?php
/**
 * Created by PhpStorm.
 * User: Branimir
 * Date: 21.05.14.
 * Time: 15:27
 */
include("DBHandler.php");

class UploadEngine {

    private $uploadDir;
    private $thumbnailDir;

    public function __construct(){
        $this->uploadDir = __DIR__ .  "/images/";
        $this->thumbnailDir = __DIR__ . "/images/thumbnails/";
    }


    public function handleImageUpload(){
        if(!isset($_FILES['image']['error']) || is_array($_FILES['image']['error'])){
            throw new RuntimeException("Invalid parameters.");
        }

        switch ($_FILES['image']['error']) {
            case UPLOAD_ERR_OK:
                break;

            case UPLOAD_ERR_NO_FILE:
                throw new RuntimeException('No file sent.');

            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new RuntimeException('Exceeded file size limit.');

            default:
                throw new RuntimeException('Unknown errors.');
        }

        if ($_FILES['image']['size'] > 1000000) {
            throw new RuntimeException('Exceeded file size limit.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        if(false === $ext = array_search(
                $finfo->file($_FILES['image']['tmp_name']),
                array(
                    'jpg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                ),
                true
            )){

        }

        //TODO: unique filename

        $uploadFile = $this->uploadDir . basename($_FILES['image']['name']);

        if(move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)){
            echo "File is valid, and was successfully uploaded.\n";
        } else {
            echo "Possible file upload attack!\n";
        }

        $this->createThumbnail($_FILES['image']['name']);
        $this->insertToDB($_SESSION['userId'], basename($_FILES['image']['name']), $_POST['name'], $_POST['description']);
        //xdebug_var_dump($uploadFile);
    }

    private function createThumbnail($fileName){
        //$file = fopen($filePath, "r");
        $filePath = $this->uploadDir . $fileName;

        $info = pathinfo($filePath);

        if(strtolower($info['extension']) == 'jpg'){
            $img = imagecreatefromjpeg($filePath);

            $width = imagesx( $img );
            $height = imagesy( $img );

            // calculate thumbnail size
            $new_width = 150;
            $new_height = floor( $height * ( 200 / $width ) );

            // create a new temporary image
            $tmp_img = imagecreatetruecolor( $new_width, $new_height );

            // copy and resize old image into new image
            imagecopyresized( $tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height );

            // save thumbnail into a file
            imagejpeg( $tmp_img, $this->thumbnailDir . $fileName);
        }
    }

    private function insertToDB($userId, $fileName, $imageName, $imageDescription){
        $db = new DBHandler();
        $pictureId = $db->getLastPictureId();
        $pictureId++;

        $db->insertImageToDatabase($pictureId, $userId,$fileName,$imageName,$imageDescription);
    }

} 