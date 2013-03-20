<?php

namespace Cherry\Web\Loaders;

use Cherry\Core\OutputBuffer;

/**
 * ATL: Another Template Language.
 *
 *
 *
 */
class PhpLoader extends Loader {
    
    public function load($filename,$output=false) {
        $ob = new OutputBuffer();
        $ob->start();
        require $filename;
        $ob->end();
        $buf = $ob->getBuffer();

        if ($output)
            echo $buf;
        return $buf;
    }
    
}
