<?php

namespace Cherry\Cli;
use Cherry\Cli\ConsoleApplication;

class ConsoleService extends ConsoleApplication {

    protected function fork() {
        $pid = pnctl_fork();
        if ($pid == 0) {
            // If we are the child
            $this->serviceMain();
            exit;
        } else {
            // Parent
        }
    }


}
