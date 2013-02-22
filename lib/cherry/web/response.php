<?php

namespace Cherry\Web;

class Response {
    public function __construct() {

    }
    public function redirect($url,$httpcode=302) {
        header("Location: {$url}", true, $httpcode);
        exit;
    }
}
