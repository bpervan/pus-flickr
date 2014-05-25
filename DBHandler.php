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

    public function getSystemUsers(){
        $usersArray = array();
        $fetchQuery = $this->connectionHandle->prepare("SELECT * FROM pusflickr.users");
        if($fetchQuery->execute()){
            while($row = $fetchQuery->fetch()){
                array_push($usersArray, new User(
                    $row['userId'],
                    $row['name'],
                    $row['surname'],
                    $row['email'],
                    $row['username']
                ));
            }
        }
        return $usersArray;
    }

    public function insertImageToDatabase($pictureId, $userId, $url, $name, $description, $privacy = 0){
        $insertQuery = $this->connectionHandle->prepare(
            "INSERT INTO pusflickr.pictures (pictureId, userId, url, name, description, privacy)
            VALUES (?,?,?,?,?,?)"
        );

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

        // tu još treba inicijalizirati dio tablice za prijateljstva

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


        $insertQuery = $this->connectionHandle->prepare(
            "INSERT INTO pusflickr.friendships (userId, friends, friendsrequests, friendsrequested)
            VALUES (?,?,?,?)");
        $emptyString = "";
        $insertQuery->bindParam(1, $user->userId);
        $insertQuery->bindParam(2, $emptyString);
        $insertQuery->bindParam(3, $emptyString);
        $insertQuery->bindParam(4, $emptyString);
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

    public function getPublicImages(){
        $retval = array();

        $fetchQuery = $this->connectionHandle->prepare("SELECT * FROM pusflickr.pictures WHERE privacy = 0");
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

    public function getImagesForProfile($userProfileId, $currentlyLoggedUserId){

        $picturesArray = array();
        $fetchQuery = $this->connectionHandle->prepare("SELECT pictures.*, friendships.friends FROM pusflickr.pictures
            INNER JOIN pusflickr.friendships ON pictures.userId = friendships.userId WHERE pictures.userId = ?");

        $fetchQuery->bindParam(1, $userProfileId);

        if($fetchQuery->execute()){
            while($row = $fetchQuery->fetch()){
                $friends = explode(",", $row['friends']);
                if(($row['userId'] == $currentlyLoggedUserId) || (in_array($currentlyLoggedUserId, $friends) || ($row['privacy'] == 0))){
                    array_push($picturesArray,new Image(
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
        }
        return $picturesArray;
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
                    $row['comment'],
                    $row['commentId']
                );
                array_push($retVal, $image);
            }
            return $retVal;
        }
        return null;
    }

    public function deleteComment($commentId){
        $deleteQuery = $this->connectionHandle->prepare("DELETE FROM pusflickr.comments WHERE comments.commentId = ?");
        $deleteQuery->bindParam(1, $commentId);

        try{
            $this->connectionHandle->beginTransaction();
            $deleteQuery->execute();
        } catch (PDOException $e){
            xdebug_var_dump($e);
            $this->connectionHandle->rollBack();
            return -1;
        }
        $this->connectionHandle->commit();
        return 0;
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
        $fetchQuery->bindParam(1, $friendId);

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


        array_push($friendRequestsArray, $userId);
        $friendRequests = implode(",", $friendRequestsArray);


        $updateQuery = $this->connectionHandle->prepare("UPDATE pusflickr.friendships SET friendsrequests = '{$friendRequests}' WHERE friendships.userId = {$friendId}");
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

    public function getFriendshipStatus($userId, $friendId){

        $fetchQuery = $this->connectionHandle->prepare("SELECT * FROM pusflickr.friendships WHERE userId = ?");
        $fetchQuery->bindParam(1, $friendId);

        if($fetchQuery->execute()){
            if($row = $fetchQuery->fetch()){
                $friendsArray = explode(",", $row['friends']);
                $count = count($friendsArray);
                for($i = 0; $i < $count; ++$i){
                    if($friendsArray[$i] == $userId){
                        return User::$FRIENDSHIP_FRIENDS;
                    }
                }

                $requestsArray = explode(",", $row['friendsrequests']);
                $count = count($requestsArray);
                for($i = 0; $i < $count; ++$i){
                    if($requestsArray[$i] == $userId){
                        return User::$FRIENDSHIP_REQUESTED;
                    }
                }

            }
        }
        return User::$FRIENDSHIP_NOT_FRIENDS;
    }

    public function getFriendshipRequests($userId){
        $requestsVar = null;
        $userArray = array();
        $fetchQuery = $this->connectionHandle->prepare("SELECT friendsrequests FROM pusflickr.friendships WHERE userId = ?");
        $fetchQuery->bindParam(1, $userId);

        if($fetchQuery->execute()){
            if($row = $fetchQuery->fetch()){
                $requestsVar = $row['friendsrequests'];
            }
        }

        if(strlen($requestsVar) === 0){
            return null;
        }

        $requestsArray = explode(",", $requestsVar);
        $query = "SELECT * FROM pusflickr.users WHERE userId = ";
        $query = $query.implode(" OR userId = ", $requestsArray);

        $friendshipRequestsQuery = $this->connectionHandle->prepare($query);
        if($friendshipRequestsQuery->execute()){
            while($row = $friendshipRequestsQuery->fetch()){
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
     * @param $userId -> Currently active user
     * @param $friendId -> Friend
     */

    public function confirmFriend($userId, $friendId){
        $this->removeFriendshipRequest($userId, $friendId);
        $this->insertFriendship($userId, $friendId);
        $this->insertFriendship($friendId, $userId);

        return 0;
    }

    public function denyFriend($userId, $friendId){
        //samo makni iz requestova
    }

    private function removeFriendshipRequest($userId, $friendId){
        $requestsVar = null;
        $fetchQuery = $this->connectionHandle->prepare("SELECT friendsrequests FROM pusflickr.friendships WHERE userId = ?");
        $fetchQuery->bindParam(1, $userId);

        if($fetchQuery->execute()){
            if($row = $fetchQuery->fetch()){
                $requestsVar = $row['friendsrequests'];
            }
        }

        $friendRequestsArray = explode(",", $requestsVar);
        if(($i = array_search($friendId, $friendRequestsArray)) !== false){
            unset($friendRequestsArray[$i]);
        }

        $requestsVar = implode(",", $friendRequestsArray);

        $updateQuery = $this->connectionHandle->prepare("UPDATE pusflickr.friendships SET friendsrequests = '{$requestsVar}' WHERE friendships.userId = {$userId}");
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

    private function insertFriendship($user1, $user2){
        $friendRequests = null;
        $fetchQuery = $this->connectionHandle->prepare("SELECT friends FROM pusflickr.friendships WHERE userId = ?");
        $fetchQuery->bindParam(1, $user1);

        if($fetchQuery->execute()){
            if($row = $fetchQuery->fetch()){
                $friendRequests = $row['friends'];
            }
        }

        if(strlen($friendRequests) >= 1){
            $friendRequestsArray = explode(",", $friendRequests);
        } else {
            $friendRequestsArray = array();
        }


        array_push($friendRequestsArray, $user2);
        $friendRequests = implode(",", $friendRequestsArray);
        xdebug_var_dump($friendRequests);


        $updateQuery = $this->connectionHandle->prepare("UPDATE pusflickr.friendships SET friends = '{$friendRequests}' WHERE friendships.userId = {$user1}");
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

    public function getFriends($userId){
        $friendsVar = null;
        $userArray = array();
        $fetchQuery = $this->connectionHandle->prepare("SELECT friends FROM pusflickr.friendships WHERE userId = ?");
        $fetchQuery->bindParam(1, $userId);

        if($fetchQuery->execute()){
            if($row = $fetchQuery->fetch()){
                $friendsVar = $row['friends'];
            }
        }

        if(strlen($friendsVar) === 0){
            return null;
        }

        $friendsArray = explode(",", $friendsVar);
        $query = "SELECT * FROM pusflickr.users WHERE userId = ";
        $query = $query.implode(" OR userId = ", $friendsArray);

        $friendsQuery = $this->connectionHandle->prepare($query);
        if($friendsQuery->execute()){
            while($row = $friendsQuery->fetch()){
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

    public function getTags($pictureId){
        $tagsVar = null;
        $userArray = array();
        $fetchQuery = $this->connectionHandle->prepare("SELECT tags FROM pusflickr.pictures WHERE pictureId = ?");
        $fetchQuery->bindParam(1, $pictureId);

        if($fetchQuery->execute()){
            if($row = $fetchQuery->fetch()){
                $tagsVar = $row['tags'];
            }
        }

        if(strlen($tagsVar) === 0){
            return null;
        }

        $tagsArray = explode(",", $tagsVar);
        $query = "SELECT * FROM pusflickr.users WHERE userId = ";
        $query = $query.implode(" OR userId = ", $tagsArray);

        $tagsQuery = $this->connectionHandle->prepare($query);
        if($tagsQuery->execute()){
            while($row = $tagsQuery->fetch()){
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

    public function insertTag($imageId, $userId){
        $tagVar = null;
        $fetchQuery = $this->connectionHandle->prepare("SELECT tags FROM pusflickr.pictures WHERE pictureId = ?");
        $fetchQuery->bindParam(1, $imageId);

        if($fetchQuery->execute()){
            if($row = $fetchQuery->fetch()){
                $tagVar = $row['tags'];
            }
        }

        if(strlen($tagVar) >= 1){
            $tagArray = explode(",", $tagVar);
        } else {
            $tagArray = array();
        }


        array_push($tagArray, $userId);
        $tagVar = implode(",", $tagArray);


        $updateQuery = $this->connectionHandle->prepare("UPDATE pusflickr.pictures SET tags = '{$tagVar}' WHERE pictures.pictureId = {$imageId}");
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

    public function removeTag($imageId, $userId){
        $tagVar = null;
        $fetchQuery = $this->connectionHandle->prepare("SELECT tags FROM pusflickr.pictures WHERE pictureId = ?");
        $fetchQuery->bindParam(1, $imageId);

        if($fetchQuery->execute()){
            if($row = $fetchQuery->fetch()){
                $tagVar = $row['tags'];
            }
        }

        $tagArray = explode(",", $tagVar);

        if(($i = array_search($userId, $tagArray)) !== false){
            unset($tagArray[$i]);
        }

        $tagVar = implode(",", $tagArray);


        $updateQuery = $this->connectionHandle->prepare("UPDATE pusflickr.pictures SET tags = '{$tagVar}' WHERE pictures.pictureId = {$imageId}");
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

    public function isUserTagged($imageId, $userId){
        $tagArray = $this->getTags($imageId);
        $count = count($tagArray);
        for($i = 0; $i < $count; ++$i){
            if($userId == $tagArray[$i]->userId){
                return true;
            }
        }
        return false;
    }
}