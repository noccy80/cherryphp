<?php

namespace Cherry\Cli;

class ConIo {

    private static $history_file = null;

    public static function readLine($prompt, ReadlineCompleter $compl = null, callable $filter = null) {
        if ($compl) {
            \readline_completion_function($compl);
        }
        $ret = \readline($prompt);
        if (self::$history_file)
            readline_write_history(self::$history_file);
        if ($filter) $ret = $filter($ret);
        return $ret;
    }
    
    public static function getAsyncReader($prompt) {
        $ar = new AsyncReader($prompt);
    }
    
    public static function setHistoryFile($file) {
        if (file_exists($file)) {
            readline_read_history($file);
        }
        self::$history_file = $file;
    }
    
    public static function write($str=null) {
        if (func_num_args()>1) {
            $args = func_get_args();
            unshift($args,STDOUT);
            call_user_func_array("fprintf",$args);
        } else
            fprintf(STDOUT,$str);
    }
    
}

class ReadlineCompleter {
    public function __invoke($input,$index) {
        return [ "hello:{$input},{$index}" ];
    }
}

class AsyncReader { 

}

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

    public function putColumns(array $data, $colwidth) {
        list($drows,$dcols) = $this->getSize();
        $cols = floor($dcols / $colwidth) - 2;
        for($n = 0; $n < count($data); $n++) {
            $this->write("%-".$colwidth."s",$data[$n]);
            if ((($n+1) % $cols) == 0) { $fl = true; $this->write("\n"); } else { $fl = false; }
        }
        if (!$fl) $this->write("\n");
    }

    public function getSize() {
        if (_IS_LINUX) {
            preg_match_all("/rows.([0-9]+);.columns.([0-9]+);/", strtolower(exec('stty -a |grep columns')), $output);
            if(sizeof($output) == 3) {
                $dh = $output[1][0];
                $dw = $output[2][0];
            }
        } elseif (_IS_WINDOWS) {
        } elseif (_IS_MACOS) {
        } else {
        }
        if (!($dw && $dh)) {
            $dw = 80;
            $dh = 25;
        }
        return array($dh,$dw);
    }

}
