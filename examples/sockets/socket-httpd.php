<?php

require_once "../../share/include/cherryphp";

define("CACHE_SIZE", 100000);
use Cherry\Expm\Net\Socket;
use Cherry\Expm\Net\SocketServer;
use Cherry\Expm\Components;

/**
 * This is the class that will take care of dispatching the messages etc. it is
 * derived from Socket, and thus we can operate on the socket within the $this
 * context as well as react to events (onConnect, onData, onTick) to do clever
 * things.
 *
 * An alternative would be to set the custom class as the userdata property.
 *
 *
 *
 */
class HttpdServerSocketClass extends Socket {
    private $lptr = 0;
    private $headers = [];
    private $data = false;
    private $fdata = null;
    private $sdata = 0;
    private $uri = null;
    private $protocol = null;
    private $method = null;
    /**
     * onConnect is called when our socket is connected to a client.
     */
    public function onConnect($peer) {
        parent::onConnect($peer);
        \debug("Connected to {$peer}");
    }
    /**
     * onData is what our main application calls when the socket has received
     * data.
     */
    public function onData($data) {
        if ($this->data === false) {
            // This WILL match if the first line is \r\n\r\n, but we assume it will
            // trail the headers, as per the RFC.
            if ($h = strpos($data,"\r\n\r\n")) {
                $hdrs = substr($data,0,$h);
                $this->data = substr($data,$h+4);
            } else { $hdrs = $data; }
            $hdr = explode("\r\n",$hdrs);
            $req = array_shift($hdr);
            \debug("{$this->uuid}: Request received; {$req}");
            list($this->method,$this->uri,$this->protocol) = explode(" ",$req,3);
            if ($this->method == "POST") {
                \debug("{$this->uuid}: Receiving {$this->uri}");
                foreach($hdr as $h) {
                    list($k,$v) = explode(":",$h,2);
                    \debug("{$this->uuid}: Got header {$k}: {$v} ");
                    $this->headers[strtolower($k)] = trim($v);
                }
                $this->fdata = fopen("/tmp/uploadtmp/".trim($this->uri,"/"),"wb");
            } elseif ($this->method == "GET") {
                \debug("{$this->uuid}: Sending {$this->uri}");
                $fn = realpath("/tmp/uploadtmp/".trim($this->uri,"/"));
                if (strpos($fn,"/tmp/uploadtmp/")===0) {
                    $this->fdata = fopen($fn,"rb");
                    $fi = finfo_open();
                    $mt = finfo_file($fi,$fn,\FILEINFO_MIME_TYPE);
                    finfo_close($fi);
                    $this->write(join("\r\n",[
                        "HTTP/1.1 200 OK",
                        "Host: 127.0.0.1:8081",
                        "Server: Higgs/1.0",
                        "Content-Length: ".filesize($fn),
                        "Content-Type: ".$mt,
                        "Connection: Close"
                    ])."\r\n\r\n");
                } else {
                    $errmsg = "Invalid request.";
                    $this->write(join("\r\n",[
                        "HTTP/1.1 500 OK",
                        "Host: 127.0.0.1:8081",
                        "Server: Higgs/1.0",
                        "Content-Length: ".strlen($errmsg),
                        "Content-Type: text/plain",
                        "Connection: Close"
                    ])."\r\n\r\n");
                }
                return;
            }
        } else {
            if ($this->method == "POST")
                $this->data.= $data;
        }
        //echo "data: len=".strlen($this->data)."\n";
        if (strlen($this->data) > CACHE_SIZE)
            $this->flushfile();
        if ($this->sdata + strlen($this->data) >= $this->headers['content-length']) {
            echo "data: completed\n";
            $this->flushfile();
            fclose($this->fdata);
            $this->write(join("\r\n",[
                "HTTP/1.1 200 OK",
                "Host: 127.0.0.1:8081",
                "Server: Higgs/1.0",
                "Content-Length: 0",
                "Connection: Close"
            ])."\r\n\r\n");
            $this->disconnect();
        }
    }
    private function flushfile() {
        $data = $this->data;
        $this->data = null;
        $this->sdata+=strlen($data);
        fwrite($this->fdata,$data,strlen($data));
    }
    /**
     * onTick is called once every loop and is responsible for dispatching the
     * message to the clients as they are received.
     */
    public function onTick() {
        if ($this->method == "GET") {
            if (($this->fdata) && !feof($this->fdata)) {
                $in = fread($this->fdata,100000);
                $lin = strlen($in);
                $this->write($in,$lin);
            } else {
                usleep(10000);
                $this->disconnect();
            }
        }
    }
}

class TestApp extends Cherry\Cli\ConsoleApplication {
    private $clog = [];
    public function chatGetLog(&$fromptr) {
        $data = array_slice($this->clog,$fromptr);
        $fromptr = count($this->clog);
        return $data;
    }
    public function chatPushLog($from,$message) {
        $message = trim($message);
        $this->clog[] = "{$from}: {$message}";
        return count($this->clog);
    }
    function server() {
        // Note how we pass our ServerSocketClass here
        $server = new SocketServer("tcp://127.0.0.1:8081", "\\HttpdServerSocketClass");
        echo "Server running. Now go ahead and post to 127.0.0.1:8081\n";
        $running = true;
        while($running) {
            // Go over the sockets that are ready to read
            foreach($server->select() as $sock) {
                $str = $sock->read(1024);
                if ($str == "") {
                    // Call close on the server rather than disconnect to free
                    // it from the pool.
                    $server->close($sock);
                } else {
                    $sock->onData($str);
                }
            }
            // Do this for each loop
            $server->each(function($client){
                $client->onTick();
            });
        }
    }

    function main() {

        $this->server();
        //$this->client();

    }
}

App::run(new TestApp());
