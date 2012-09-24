<?php
namespace extensions\cherrypanel;
use Cherry\Base\Event;
use Cherry\Extension\Extension;

class CherryPanelExtension extends Extension {

    function initialize() {
        
        // Event observers

        // Set up routing for our controller
        $rt = \Cherry\Mvc\Router\StaticRoutes::getInstance();
        $rt->addRoute('/cherrypanel/:method/:args', '\CherryPanel\Controller');
        
    }

}

return new CherryPanelExtension();
