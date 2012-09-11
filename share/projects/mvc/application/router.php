<?php

namespace MyApp;
class Router extends \Lepton\Mvc\Router\Base {

    function route(\Lepton\Mvc\Request $request) {
        printf("in router...\n");
    }

}
