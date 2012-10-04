<?php

namespace Cherry;

abstract class CacheProvider {

    abstract function get($key);
    abstract function set($key,$value,$expires);
    abstract function has($key);

}
