<?php

namespace Cherry\Cli;
use Cherry\Cli\ConsoleApplication;

abstract class ConsoleService extends ConsoleApplication {

    abstract protected function serviceMain();

    /**
     *
     *
     * @note For fork() to work, you must use declare(ticks=1) in the same file
     *      as your event handler. If you leave this out, your handlers will
     *      never be called.
     * @return bool True on success.
     */
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
        
}
