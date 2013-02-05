<?php

namespace xenon;

$path = getenv("CHERRY_LIB");
require_once $path . '/lib/bootstrap.php';
\Cherry\Base\PathResolver::getInstance()->setAppPath(XENON_ROOT);

define("XENON_FWAPI","\\xenon\\cherryphp");

class cherryphp {
    public function addAutoloader($path,$base=null) {
        (new \Cherry\Base\Autoloader($path,$base))->register();
    }
}
