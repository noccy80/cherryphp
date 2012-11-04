<?php

namespace Cherry\Pong\Adapters;

use Cherry\Pong\Adapter;

class LongPollingAdapter extends Adapter {
    public function prepare() {
        $rt = app::getInstance()->mvc->routing;
        $rt->addRoute(self::ADAPTER_URL_PREFIX.'/channel/'.$cid, array(__CLASS__,'onrequest');
    }
    public function onrequest(Request $request) {
        
    }
}
