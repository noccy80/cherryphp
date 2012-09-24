<?php

namespace Cherry\Cli\Adapters;


class AnsiConsole extends \Cherry\Cli\ConsoleAdapter {
    
    public function putMessage($string, $msgclass=null) {
        if ($msgclass == \Cherry\Cli\ConsoleAdapter::CLASS_ERROR) {
            fwrite(STDERR,$string);
        } else {
            fwrite(STDOUT,$string);
        }
    }
    
}