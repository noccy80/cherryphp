<?php
namespace extensions\cherrypanel;
use Cherry\Base\Event;
use Cherry\Extension\Extension;

class CherryPanelExtension extends Extension {

    function initialize() {
        Event::observe('onspecialtag', array($this,'onTag'));
        /*
        $app = Application::getInstance();
        $rt = $app->mvc->routes;
        $rt->addRoute('/cherrypanel/*', '\Extensions\CherryPanel\Controller');
        */
    }

    function onTag($tag,array $props) {
        if ($tag == '@uuid') {
            return \Cherry\Crypto\Uuid::getInstance()->generate();
        }
    }

}

return new CherryPanelExtension();
