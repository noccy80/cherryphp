<?php

namespace Cherry\Pong;

use Cherry\Pong\AbstractAdapter;

class Channel {

    private $seqno = 0;
    private $encrypted = false;
    private $adapter = null;
    private $channelid = null;

    public function __construct($channelid, AbstractAdapter $adapter) {
        $this->channelid = $channelid;
        $this->adapter = $adapter;
    }

    public function push($type,$data) {

        $json = json_encode((object)[
            'type' => $type,
            'seqno' => $this->seqno++,
            'data' => $data
        ]);
        $this->adapter->sendPackage($json);
    }

    public function poll() {

        

    }

}
