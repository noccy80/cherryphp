<?php
namespace extensions\CherryBar;
use Cherry\Base\Event;
use Cherry\Extension\Extension;

class CherryBarExtension extends Extension {

    function initialize() {
        Event::observe('cherry:mvc.render.head', array($this,'createBar'));
    }
    
    function createBar() {
        return '<link rel="stylesheet" href="/meta/cherrybar/cherrybar.css">'."\n".
                '<script type="text/javascript" src="/meta/cherrybar/cherrybar.js"></script>'."\n";
    
    }

}

return new CherryBarExtension();
