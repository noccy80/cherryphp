<?php

namespace cherry\Mvc\Router;

class Simple extends Base {

    function route(\cherry\Mvc\Request $request) {
        \Cherry\Log(\Cherry\LOG_DEBUG,"Routing request: %s", $request);
        $cobj = $this->checkStaticRoutes($request);
        if (!$cobj) {
            $cobj = \cherry\Mvc\Controller\Base::factory('\MyApp\Controllers\IndexController',$request);
            $cobj->method = 'index';
        }
        if (is_object($cobj)) $cobj->invoke();
    }

}
