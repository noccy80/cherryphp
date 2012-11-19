<?php

namespace Cherry\Mvc;

class Loader {
    function __construct() {/*
        'Cherry\Mvc\Application',
        'Cherry\Mvc\Session',
        'Cherry\Mvc\Response',
        'Cherry\Mvc\Request',
        'Cherry\Mvc\Cookies',
        'Cherry\Mvc\Widget'*/
        \App::extend('router', new \Cherry\Mvc\Router());
        \App::extend('server', new \Cherry\Mvc\Server());
    }
}

abstract class EventsEnum {
    const REQUEST_CREATE        = 'cherry:mvc.request.create';
    const RENDER_HEAD           = 'cherry:mvc.render.header';
    const RENDER_FOOT           = 'cherry:mvc.render.foot';
    const RENDER_SPECIALTAG     = 'cherry:mvc.render.specialtag';
}
