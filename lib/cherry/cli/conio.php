<?php

namespace cherry\cli;

class Console {

    private static $instance = null;

    private $updatemode = false;
    private $linemode = false;

    static function getConsole() {
        if (!self::$instance) {
            self::$instance = new Console();
        }
        return self::$instance;
    }
    
    public function warn($string,$args=null) {
        $args = func_get_args();
        if ($this->updatemode || $this->linemode) {
            $pre = "\n";
            $this->updatemode = false;
            $this->linemode = false;
        } else {
            $pre = '';
        }
        $str = call_user_func_array('sprintf',$args);
        if (substr($str,-1)!="\n") {
            $str.="\n";
        }
        $this->linemode = false;
        fprintf(STDOUT,"%s", $pre);
        fprintf(STDERR,"%s", $str);
    }
    
    public function write($string,$args=null) {
        $args = func_get_args();
        if ($this->updatemode) {
            $pre = sprintf("\033[2K\r");
            $this->updatemode = false;
        } else { $pre = ''; }
        $str = call_user_func_array('sprintf',$args);
        if (substr($str,-1)!="\n") {
            $this->linemode = true;
        } else {
            $this->linemode = false;
        }
        fprintf(STDOUT,"%s", $pre.$str);
    }
    
    public function update($string,$args=null) {
        $args = func_get_args();
        $pre = '';
        if ($this->linemode) {
            $pre.= "\n";
            $linemode = false;
        }
        $pre.= sprintf("\033[2K\r");
        $str = call_user_func_array('sprintf',$args);
        $str = str_replace("\n","",$str);
        fprintf(STDOUT,"%s", $pre.$str);
        $this->updatemode = true;
    }
    
    public function prompt($prompt, $default) {
    
        $prompt = sprintf("%s [%s]: ", $prompt, $default);
        $str = readline($prompt);
        if ($str) return $str;
        return $default;
    
    }

}
