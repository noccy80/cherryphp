<?php

namespace Cherry\Base;

class PathResolver {

    use \Cherry\Traits\SingletonAccess;

    private $apppath = null;

    public function __construct() {
    }

    public function getPath($path) {
        $ret = $path;
        $ret = str_replace('{APP}',$this->apppath,$ret);
        $ret = str_replace('{DATA}',$this->apppath.'/data',$ret);
        $ret = str_replace('{CACHE}',$this->apppath.'/cache',$ret);
        $ret = str_replace('{PUBLIC}',$this->apppath.'/public',$ret);
        $ret = str_replace('{SYSTEM}','/etc/cherryphp',$ret);
        $ret = str_replace('{USER}',rtrim(getenv("HOME"),'/').'/.cherryphp',$ret);
        return $ret;
    }

    public function setAppPath($path) {
        \debug("Application path set to {$path}");
        $this->apppath = realpath($path);
    }

}
