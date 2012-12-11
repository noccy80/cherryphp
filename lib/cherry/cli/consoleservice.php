<?php

namespace Cherry\Cli;
use Cherry\Cli\ConsoleApplication;

abstract class ConsoleService extends ConsoleApplication {

    abstract protected function serviceMain();

    protected function fork() {
        $pid = pcntl_fork();
        if ($pid == 0) {
            // If we are the child
            echo posix_getpid(),"\n";
            $this->serviceMain();
            exit;
        } elseif($pid == -1) {
            return false;
        } else {
            return true;
            // Parent
        }
    }
    
    protected function attachSignal($signal, $handler, $restart=false) {
        if (!is_callable($handler))
            user_error("Signal handler is not callable.");
        if ($signal === null) $signal = SIG_DFL;
        if ($signal === false) $signal = SIG_IGN;
        pcntl_signal($signal,$handler,$restart);
    }

    protected function log($str,$vararg=null) {
         $args = func_get_args();
         $lstr = call_user_func_array('sprintf',$args)."\n";
         echo $lstr;
    }
    
}
