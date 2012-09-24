<?php
namespace extensions\uuidextensions;
use Cherry\Base\Event;

class UuidExtension extends \Cherry\Extension\Extension {

    function initialize() {
        Event::observe(\Cherry\Mvc\EventsEnum::RENDER_SPECIALTAG, array($this,'onTag'));
    }

    function onTag($tag,array $props) {
        if ($tag == '@uuid') {
            \Cherry\Log(\Cherry\LOG_DEBUG,"Generating UUID for @uuid metatag.");
            return \Cherry\Crypto\Uuid::getInstance()->generate();
        }
    }

}

return new UuidExtension();
