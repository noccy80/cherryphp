<?php

namespace MyApp;
class Router extends \cherry\Mvc\Router\Base {

    function route(\cherry\Mvc\Request $request) {
        printf("in router...\n");
    }

}
