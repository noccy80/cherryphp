<?php

namespace Cherry\Cli\Adapters;


class SimpleConsole extends \Cherry\Cli\ConsoleAdapter {
    
    public function putMessage($string, $msgclass=null) {
        $string = $this->stripAnsi($string);
        fwrite(STDOUT,$string);
    }

    function stripAnsi($string) {
        return preg_replace('/\e\[[;?0-9]*[0-9A-Za-z]/', '', $string);
    }    
    
}