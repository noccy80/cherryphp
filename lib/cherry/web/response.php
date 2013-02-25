<?php

namespace Cherry\Web;

class Response {
    public function __construct() {

    }
    public function redirect($url,$httpcode=302) {
        header("Location: {$url}", true, $httpcode);
        exit;
    }
    public function sendFile($file) {
        $lastmod = filemtime($file);
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $ifmod = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
            if ($ifmod >= $lastmod) {
                header('Not Modified',true,304);
                return 304;
            }
        }
        $ct = null;
        // Apply content type
        foreach([
            '*.css' => 'text/css',
            '*.js' => 'text/javascript'
        ] as $ptn => $ct)
            if (fnmatch($ptn,$file))
                $ctype = $ct;
        // If no match, try to determine
        if (empty($ctype)) $ctype = mime_content_type($file);
        // Set headers
        header('Content-Type: '.$ctype);
        header('Content-Length: '.filesize($file));
        header('Last-Modified: '.gmdate('D, d M Y H:i:s \G\M\T', $lastmod));
        $this->contentlength = filesize($file);
        readfile($file);
        return 200;
    }

}
