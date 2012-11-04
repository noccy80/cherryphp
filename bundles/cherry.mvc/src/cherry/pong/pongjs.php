<?php

namespace Cherry\Pong;

class PongJs {

    public static function getChannel($cid) {
        return 'cherry.pong.channel.'.$cid;
    }

}
