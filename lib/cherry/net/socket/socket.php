<?php

namespace cherry\net\socket;
use cherry\base\EventEmitter;
use cherry\net\proxy\Proxy;

const SOCK_PROXY = 0x20;
const SOCK_NONBLOCKING = 0x08;
const SOCK_IPV6 = 0x10;

abstract class Socket extends EventEmitter {
    protected $host = null;
    protected $port = null;
    protected $flags = 0x00;

}

