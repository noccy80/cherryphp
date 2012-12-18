<?php

namespace Cherry\Proc;

class Posix {
    
    public static function getUserByName($username) {
        $user = posix_getpwnam($username);
    }
    
    public static function getGroupByName($groupname) {
        $group = posix_getgrnam($groupnale);
        
    }
    
    public static function getUserByUid($uid) {
        $user = posix_getpwuid($uid);
    }
    
    public static function getGroupByGid($gid) {
        $group = posix_getgrgid($gid);
    }
    
    public static function getLogin() {
        return posix_getlogin();
    }
    
    public static function getEffectiveUser() {
        return get_current_user();
    }
    
    public static function getProcessUid() {
        return get_current_user();
    }
    
    public static function setProcessUid($uid) {
        posix_seteuid($uid);
    }
    
    public static function setProcessGid($gid) {
        posix_setegid($gid);
    }
    
}