<?php

namespace Cherry\Core;

class PathResolver {

    use \Cherry\Traits\TSingletonAccess;

    private $apppath = null;

    public function __construct() {
    }

    public function getPath($path) {
        $ret = $path;
        $ret = str_replace('{APP}', CHERRY_APP,$ret);
        $ret = str_replace('{DATA}',__APP__.'/data',$ret);
        $ret = str_replace('{CACHE}',__APP__.'/cache',$ret);
        $ret = str_replace('{CONFIG}',__APP__.'/config',$ret);
        $ret = str_replace('{PUBLIC}',__APP__.'/public',$ret);
        $ret = str_replace('{SYSTEM}','/etc/cherryphp',$ret);
        $ret = str_replace('{USER}',rtrim(getenv("HOME"),'/').'/.cherryphp',$ret);
        return $ret;
    }

    public function setAppPath($path) {
        \debug("Application path set to {$path}");
        $this->apppath = realpath($path);
    }

    public static function path($path) {
        return PathResolver::getInstance()->getPath($path);
    }

}
