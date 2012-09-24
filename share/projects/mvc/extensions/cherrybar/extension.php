<?php
namespace extensions\CherryBar;
use Cherry\Base\Event;
use Cherry\Extension\Extension;

class CherryBarExtension extends Extension {

    function initialize() {
        Event::observe('cherry:mvc.render.head', array($this,'createBar'));
        Event::observe('onspecialtag', array($this,'onTag'));
    }
    
    function createBar() {
        return '<link rel="stylesheet" href="/meta/cherrybar/cherrybar.css">'."\n".
                '<script type="text/javascript" src="/meta/cherrybar/cherrybar.js"></script>'."\n";
    
    }

    function onTag($tag,array $props) {
        if ($tag == '@header') {
            return Event::invoke('cherry:mvc.render.head');
        }
    }

}

return new CherryBarExtension();
