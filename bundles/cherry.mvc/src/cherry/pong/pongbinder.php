<?php

namespace Cherry\Pong;

use Cherry\Pong\Channel;
use Cherry\Event;

class PongBinder {

    use SingletonAccess;

    const EVT_ONOPEN = 'pong:channel.open';
    const EVT_ONINIT = 'pong:channel.init';

    private $pongs;

    public function __construct() {

        $this->pongs = Array();
        Event::observe(\Cherry\Mvc\EVT_ON_HEADER, array($this, 'onHeader'));

    }

    public function bind($callback) {

        // return channelid
        // add code to initialize channel to output

    }

    public function open(Channel $channel) {

        $channelid = $channel->channelid;
        $bind = $this->getBindByChannelId($channelid);
        $bind(self::EVT_ONOPEN, $channel);

    }

    public function onHeader() {

        // Write header
        $pongjs = new \Cherry\Cache\CachableFile(app::getBundle('cherry.mvc')->staticFile('js/pong.js'));
        $channels = \Cherry\Pong\Pong::getChannels();

    }

}


