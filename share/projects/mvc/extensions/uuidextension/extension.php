<?php
namespace extensions\uuidextensions;
use Cherry\Base\Event;

class UuidExtension extends \Cherry\Extension\Extension {

    function initialize() {
        Event::observe('onspecialtag', array($this,'onTag'));
    }

    function onTag($tag,array $props) {
        if ($tag == '@uuid') {
            return \Cherry\Crypto\Uuid::getInstance()->generate();
        }
    }

}

return new UuidExtension();
