<?php

namespace xenon\frameworks;

use \xenon\xenon;

class cherryphp {
    public function addAutoloader($path,$base=null) {
        (new \Cherry\Base\Autoloader($path,$base))->register();
    }
    public static function bootstrap() {
        static $done = 0;
        if ($done) return;
        $done = 1;
        foreach((array)xenon::config('framework.preload') as $preload) {
            class_exists($preload,true);
        }        
        $dm = (int)xenon::config('framework.debuglevel');
        if ($dm == 0) setenv("DEBUG=0");
        if ($dm >= 1) setenv("DEBUG=1");
        if ($dm >= 2) setenv("DEBUG_VERBOSE=1");
        
        $path = getenv("CHERRY_LIB");
        require_once $path . '/lib/bootstrap.php';
        \Cherry\Base\PathResolver::getInstance()->setAppPath(XENON_ROOT);
        
        define("XENON_FWAPI",'\xenon\frameworks\cherryphp');
    }
}

cherryphp::bootstrap();