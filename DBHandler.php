<?php
include("DBConst.php");
include("User.php");
include("Image.php");
include("Comment.php");
/**
 * Created by PhpStorm.
 * User: Branimir
 * Date: 15.05.14.
 * Time: 21:46
 */

class DBHandler {

    private $connectionHandle;

    public function __construct(){
        $opt = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        );
        $this->connectionHandle = new PDO('mysql:host=localhost', DBUSER, DBPASSWORD, $opt);

        $utf8query = $this->connectionHandle->prepare("SET NAMES 'utf8'");
        $utf8query->execute();
    }

    public function __destruct(){
        $this->connectionHandle = null;
    }

    public function checkUser($username, $hashedpassword){
        $checkUserQuery = $this->connectionHandle->prepare("SELECT * FROM pusflickr.users where username = ? and password = ?");
        $checkUserQuery->bindParam(1, $username);
        $checkUserQuery->bindParam(2, $hashedpassword);
        if($checkUserQuery->execute()){
            if($row = $checkUserQuery->fetch()){
                $user = new User($row['userId'], $row['name'], $row['surname'], $row['email'], $row['username']);
                return $user;
            }
        }
        return null;
    }

    public function insertImageToDatabase($pictureId, $userId, $url, $name, $description){
        $insertQuery = $this->connectionHandle->prepare(
            "INSERT INTO pusflickr.pictures (pictureId, userId, url, name, description, privacy)
            VALUES (?,?,?,?,?,?)"
        );
        $privacy = 0;

        $insertQuery->bindParam(1, $pictureId);
        $insertQuery->bindParam(2, $userId);
        $insertQuery->bindParam(3, $url);
        $insertQuery->bindParam(4, $name);
        $insertQuery->bindParam(5, $description);
        $insertQuery->bindParam(6, $privacy);

        $insertQuery->execute();
    }

    public function getLastPictureId(){
        $fetchQuery = $this->connectionHandle->prepare("SELECT pictureId FROM pusflickr.pictures ORDER BY pictureId DESC LIMIT 1");
        if($fetchQuery->execute()){
            if($row = $fetchQuery->fetch()){
                return $row['pictureId'];
            } else {
                return 1;
            }
        }
        return 1;
    }

    public function getLastUserId(){
        $fetchQuery = $this->connectionHandle->prepare("SELECT userId FROM pusflickr.users ORDER BY userId DESC LIMIT 1");
        if($fetchQuery->execute()){
            if($row = $fetchQuery->fetch()){
                return $row['userId'];
            } else {
                return 1;
            }
        }
        return 1;
    }

    public function insertUser(User $user, $password){
        $insertQuery = $this->connectionHandle->prepare(
            "INSERT INTO pusflickr.users (userId, username, password, name, surname, email)
            VALUES (?,?,?,?,?,?)"
        );

        $insertQuery->bindParam(1, $user->userId);
        $insertQuery->bindParam(2, $user->username);
        $insertQuery->bindParam(3, $password);
        $insertQuery->bindParam(4, $user->name);
        $insertQuery->bindParam(5, $user->surname);
        $insertQuery->bindParam(6, $user->email);
        try{
            $this->connectionHandle->beginTransaction();
            $insertQuery->execute();
        } catch (PDOException $e){
            $this->connectionHandle->rollBack();
            return -1;
        }
        $this->connectionHandle->commit();
        return 0;
    }

    public function getImagesForIndex(){
        $retval = array();

        $fetchQuery = $this->connectionHandle->prepare("SELECT * FROM pusflickr.pictures WHERE privacy = 0 LIMIT 8");
        if($fetchQuery->execute()){
            while($row = $fetchQuery->fetch()){
                array_push($retval,
                    new Image(
                        $row['pictureId'],
                        $row['userId'],
                        $row['likes'],
                        $row['url'],
                        $row['name'],
                        $row['description'],
                        $row['privacy']
                ));
            }
        }
        return $retval;
    }

    public function getImage($id){
        $fetchQuery = $this->connectionHandle->prepare("SELECT * FROM pusflickr.pictures WHERE pictureId = ?");
        $fetchQuery->bindParam(1, $id);

        if($fetchQuery->execute()){
            if($row = $fetchQuery->fetch()){
                $image = new Image(
                    $row['pictureId'],
                    $row['userId'],
                    $row['likes'],
                    $row['url'],
                    $row['name'],
                    $row['description'],
                    $row['privacy']
                );
                return $image;
            }
        }
        return null;
    }

    public function insertLike($imageId, $userId){
        $likesVar = null;
        $fetchQuery = $this->connectionHandle->prepare("SELECT likes FROM pusflickr.pictures WHERE pictureId = ?");
        $fetchQuery->bindParam(1, $imageId);

        if($fetchQuery->execute()){
            if($row = $fetchQuery->fetch()){
                $likesVar = $row['likes'];
            }
        }

        if(strlen($likesVar) >= 1){
            $likesArray = explode(",", $likesVar);
        } else {
            $likesArray = array();
        }


        array_push($likesArray, $userId);
        $likesVar = implode(",", $likesArray);


        $updateQuery = $this->connectionHandle->prepare("UPDATE pusflickr.pictures SET likes = '{$likesVar}' WHERE pictures.pictureId = {$imageId}");
        try{
            $this->connectionHandle->beginTransaction();
            $updateQuery->execute();
        } catch (PDOException $e){
            $this->connectionHandle->rollBack();
            echo $e->getMessage();
            return -1;
        }
        $this->connectionHandle->commit();
        return 0;
    }

    public function removeLike($imageId, $userId){
        $likesVar = null;
        $fetchQuery = $this->connectionHandle->prepare("SELECT likes FROM pusflickr.pictures WHERE pictureId = ?");
        $fetchQuery->bindParam(1, $imageId);

        if($fetchQuery->execute()){
            if($row = $fetchQuery->fetch()){
                $likesVar = $row['likes'];
            }
        }

        $likesArray = explode(",", $likesVar);

        if(($i = array_search($userId, $likesArray)) !== false){
            unset($likesArray[$i]);
        }

        $likesVar = implode(",", $likesArray);


        $updateQuery = $this->connectionHandle->prepare("UPDATE pusflickr.pictures SET likes = '{$likesVar}' WHERE pictures.pictureId = {$imageId}");
        try{
            $this->connectionHandle->beginTransaction();
            $updateQuery->execute();
        } catch (PDOException $e){
            $this->connectionHandle->rollBack();
            echo $e->getMessage();
            return -1;
        }
        $this->connectionHandle->commit();
        return 0;
    }

    /**
     * @param $imageId
     * @return Array of Users who liked image with $imageId
     */
    public function getLikes($imageId){
        $likesVar = null;
        $userArray = array();
        $fetchQuery = $this->connectionHandle->prepare("SELECT likes FROM pusflickr.pictures WHERE pictureId = ?");
        $fetchQuery->bindParam(1, $imageId);

        if($fetchQuery->execute()){
            if($row = $fetchQuery->fetch()){
                $likesVar = $row['likes'];
            }
        }

        if(strlen($likesVar) === 0){
            return null;
        }

        $likesArray = explode(",", $likesVar);
        $query = "SELECT * FROM pusflickr.users WHERE userId = ";
        $query = $query.implode(" OR userId = ", $likesArray);

        $likesQuery = $this->connectionHandle->prepare($query);
        if($likesQuery->execute()){
            while($row = $likesQuery->fetch()){
                array_push($userArray, new User(
                    $row['userId'],
                    $row['name'],
                    $row['surname'],
                    $row['email'],
                    $row['username']
                ));
            }
            return $userArray;
        }
        return null;
    }

    /**
     * Checks if user liked a specific image
     * @param $imageId -> Image ID
     * @param $userId -> User ID
     * @return True if user with $userId liked picture with $pictureId, false otherwise
     */
    public function hasUserLike($imageId, $userId){
        $likeArray = $this->getLikes($imageId);
        $count = count($likeArray);
        for($i = 0; $i < $count; ++$i){
            if($userId == $likeArray[$i]->userId){
                return true;
            }
        }
        return false;
    }

    public function getLastCommentId(){
        $fetchQuery = $this->connectionHandle->prepare("SELECT commentId FROM pusflickr.comments ORDER BY commentId DESC LIMIT 1");
        if($fetchQuery->execute()){
            if($row = $fetchQuery->fetch()){
                return $row['commentId'];
            } else {
                return 0;
            }
        }
        return 0;
    }

    public function insertComment(Comment $comment){
        $commentId = $this->getLastCommentId();
        $commentId++;

        $insertQuery = $this->connectionHandle->prepare(
            "INSERT INTO pusflickr.comments (commentId, userId, pictureId, comment)
            VALUES (?,?,?,?)"
        );


        $insertQuery->bindParam(1, $commentId);
        $insertQuery->bindParam(2, $comment->userId);
        $insertQuery->bindParam(3, $comment->imageId);
        $insertQuery->bindParam(4, $comment->comment);

        try{
            $this->connectionHandle->beginTransaction();
            $insertQuery->execute();
        } catch (PDOException $e){
            $this->connectionHandle->rollBack();
            return -1;
        }
        $this->connectionHandle->commit();
        return 0;

    }

    public function getComments($imageId){
        $retVal = array();
        $fetchQuery = $this->connectionHandle->prepare("SELECT * FROM pusflickr.comments WHERE pictureId = ? ORDER BY commentId DESC");
        $fetchQuery->bindParam(1, $imageId);

        if($fetchQuery->execute()){
            while($row = $fetchQuery->fetch()){
                $image = new Comment(
                    $row['userId'],
                    $row['pictureId'],
                    $row['comment']
                );
                array_push($retVal, $image);
            }
            return $retVal;
        }
        return null;
    }

    public function getUser($userId){
        $fetchQuery = $this->connectionHandle->prepare("SELECT * FROM pusflickr.users WHERE userId = ?");
        $fetchQuery->bindParam(1, $userId);

        if($fetchQuery->execute()){
            if($row = $fetchQuery->fetch()){
                $user = new User(
                    $row['userId'],
                    $row['name'],
                    $row['surname'],
                    $row['email'],
                    $row['username']
                );
                return $user;
            }
        }
        return null;
    }

    public function makeFriendshipRequest($userId, $friendId){
        $friendRequests = null;
        $fetchQuery = $this->connectionHandle->prepare("SELECT friendsrequests FROM pusflickr.friendships WHERE userId = ?");
        $fetchQuery->bindParam(1, $userId);

        if($fetchQuery->execute()){
            if($row = $fetchQuery->fetch()){
                $friendRequests = $row['friendsrequests'];
            }
        }

        if(strlen($friendRequests) >= 1){
            $friendRequestsArray = explode(",", $friendRequests);
        } else {
            $friendRequestsArray = array();
        }


        array_push($friendRequestsArray, $friendId);
        $friendRequests = implode(",", $friendRequestsArray);


        $updateQuery = $this->connectionHandle->prepare("UPDATE pusflickr.friendships SET friendsrequests = '{$friendRequests}' WHERE friendships.userId = {$userId}");
        try{
            $this->connectionHandle->beginTransaction();
            $updateQuery->execute();
        } catch (PDOException $e){
            $this->connectionHandle->rollBack();
            echo $e->getMessage();
            return -1;
        }
        $this->connectionHandle->commit();
        return 0;
    }
} 