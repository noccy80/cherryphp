<?php

namespace Cherry\Core\Utils;

use \Cherry\Web\Request;
use \Cherry\Web\Response;

class HttpdLogger {

    function logHit(Request $req, Response $resp) {
        $log = sprintf("%15s [%s (%s)] %d %s %s %s",
            date("d-M-y h:i:s",$req->getTimestamp()),
            $req->getRemoteIp(),
            $req->getRemoteHost(),
            $resp->getStatus(),
            $req->getRequestMethod(),
            $req->getRequestURL(),
            ($resp->contentLength)?"(".$resp->contentLength." bytes)":""
        );
        fprintf(STDERR, $log."\n");
    }
}
