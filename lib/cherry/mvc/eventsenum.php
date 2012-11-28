<?php

namespace Cherry\Mvc;

abstract class EventsEnum {
    const REQUEST_CREATE        = 'cherry:mvc.request.create';
    const RENDER_HEAD           = 'cherry:mvc.render.header';
    const RENDER_FOOT           = 'cherry:mvc.render.foot';
    const RENDER_SPECIALTAG     = 'cherry:mvc.render.specialtag';
}
