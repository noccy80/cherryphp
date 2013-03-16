<?php

namespace Cherry\Core\Ipc;

class IpcQueue extends IpcObject {

    public function open() {
        if ($this->hqueue)
            return true;
        $qh = msg_get_queue($this->ipckey,0600);
        if (!$qh)
            throw new \Exception("Unable to open queue (key={$this->ipckey})");
        $this->hqueue = $qh;
    }

    public function close() {

    }

}
