<?php

namespace Cherry\Core\Auth;

abstract class AuthSource {

    abstract public function testPassword($user,$pass);
    
    abstract public function setPassword($user,$pass);
    
    abstract public function addUser(UserRecord $user);
    
    abstract public function removeUser(UserRecord $user);
    
    abstract public function updateUser(UserRecord $user);
 
}

class DatabaseAuthSource extends AuthSource {

    public function checkPassword($user,$pass) {
        $hasher = ObjMan::get("/auth/hasher");
        
        $ok = $hasher->checkHash($hash,$pass);
    }
    
    public function setPassword($user,$pass) {
    }
    
    public function addUser(UserRecord $user) {
    }
    
    public function removeUser(UserRecord $user) {
    }
    
    public function updateUser(UserRecord $user) {
    }

}

ObjMan::register("/auth/source/default", new DatabaseAuthSource("default"));

