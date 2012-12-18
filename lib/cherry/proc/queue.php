<?php

namespace Cherry\Proc;

use Cherry;

class Queue {
    
    const SCOPE_GLOBAL = 0;
    const SCOPE_APP = 1;
    const SCOPE_PID = 2;
    
    private
        $key = null,
        $queue = null;
    
    public function __construct($file,$scope = self::SCOPE_GLOBAL, $size = 65535) {
        if (!$file)
            throw new \BadArgumentException("Expected filename to Queue constructor");
        $mf = $file . (($scope==self::SCOPE_PID)?'.'.getmypid():'');
        if (!file_exists($mf))
            touch($mf);
        $this->key = fileinode($mf);
        if (!msg_queue_exists($this->key)) {
            Cherry\Debug("Creating IPC queue (0x%x) for %s", $this->key, $mf);
            $this->queue = msg_get_queue($this->key, 0600);
        } else {
            Cherry\Debug("Reopening IPC queue (0x%x) for %s", $this->key, $mf);
            $this->queue = msg_get_queue($this->key, 0600);
        }
    }
    
    public function send($data,$type=1) {
        if (!msg_send($this->queue,$type,$data,true,true,$error)) {
            Cherry\Debug("Error sending to IPC queue 0x%x: %s", $this->key, $error);
        }
    }
    
    public function receive($type=0,&$msgtype=null) {
        if (!msg_receive($this->queue,$type,$msgtype,65535,$msgdata,true,MSG_IPC_NOWAIT,$error)) {
            if ($error == MSG_ENOMSG)
                return null;
            Cherry\Debug("Error reading from IPC queue 0x%x: %s", $this->key, $error);
            return null;
        }
        return $msgdata;
    }
    
}