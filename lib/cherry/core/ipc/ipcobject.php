<?php

namespace Cherry\Core\Ipc;

abstract class IpcObject  {

    use \Cherry\Traits\TDebug;

    protected $ipckey;

    public function __construct($file=null) {
        // Generate token for shm segment
        $this->ipckey = IpcObject::generateKey($file);
        $this->debug("IpcObject: Using {$file} for ipc key {$this->ipckey}");
    }

    public function __destruct() {
        if ($this->isOpen()) {
            $this->close();
        }
    }

    public static function generateKey($file=null) {
        if (!$file) {
            if (!empty($argv[0])) {
                $file = $argv[0];
            } elseif (!empty($_SERVER["SCRIPT_FILENAME"])) {
                $file = $_SERVER["SCRIPT_FILENAME"];
            } else {
                $file = tempnam(null,"ipc");
            }
        }
        $key = fileinode($file);
        return $key;
    }

    public function getKey() {
        return $this->ipckey;
    }

    abstract public function isOpen();
    abstract public function open();
    abstract public function close();
    abstract public function destroy();

}
