<?php

namespace Cherry\Core\Ipc;

abstract class IpcObject  {

    protected $ipckey;

    public function __construct() {
        // Generate token for shm segment
        $this->ipckey = IpcObject::generateKey($file);
        \debug("IpcObject: Using {$file} for ipc key {$this->ipckey}");
    }

    public static function generateKey() {
        if (!empty($argv[0])) {
            $file = $argv[0];
        } elseif (!empty($_SERVER["SCRIPT_FILENAME"])) {
            $file = $_SERVER["SCRIPT_FILENAME"];
        } else {
            $file = tempnam(null,"ipc");
        }
        $key = fileinode($file);
        return $key;
    }

    public function getKey() {
        return $this->ipckey;
    }

    abstract public function open();
    abstract public function close();

}
