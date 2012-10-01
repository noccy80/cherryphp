<?php

namespace CherryUtil;

abstract class EventsEnum {
    const REQUEST_CREATE        = 'cherry:mvc.request.create';
    const RENDER_HEAD           = 'cherry:mvc.render.header';
    const RENDER_FOOT           = 'cherry:mvc.render.foot';
    const RENDER_SPECIALTAG     = 'cherry:mvc.render.specialtag';
}

require_once 'src/cherryutil/command.php';
require_once 'src/cherryutil/fileops.php';
require_once CHERRY_LIB.'/lib/cherry/cli/conio.php';
require_once 'src/cherryutil/commands/all.php';

return array(
    'autoload' => array(
    ),
    'depends' => array(
    )
);
