<?php

namespace Cherry\Cli\Adapters;


class NullConsole extends \Cherry\Cli\ConsoleAdapter {
    
    public function putMessage($string, $msgclass=null) {
    }

    function stripAnsi($string) {
        return preg_replace('/\e\[[;?0-9]*[0-9A-Za-z]/', '', $string);
    }    
    
}