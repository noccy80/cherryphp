<?php

namespace Cherry\Cwt;

abstract class EventsEnum {
    const MOUSE_DOWN            = 'cherry:cwt.mousedown';
    const MOUSE_UP              = 'cherry:cwt.mouseup';
}

return array(
    'autoload' => array(
        'Cherry\Cwt\Cwt',
    ),
    'depends' => array(
        'cherry.db'
    )
);
