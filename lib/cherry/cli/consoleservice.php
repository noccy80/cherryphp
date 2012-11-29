<?php

namespace Cherry\Cli;
use Cherry\Cli\ConsoleApplication;

abstract class ConsoleService extends ConsoleApplication {

    abstract protected function serviceMain();

    protected function fork() {
        $pid = pcntl_fork();
        if ($pid == 0) {
            // If we are the child
            $this->serviceMain();
            exit;
        } else {
            // Parent
        }
    }


}
