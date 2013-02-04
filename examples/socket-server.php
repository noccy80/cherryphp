<?php

define("XENON","cherryphp/trunk");
require_once("xenon/xenon.php");

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
class ServerSocketClass extends Socket {
    private $lptr = 0;
    /**
     * onConnect is called when our socket is connected to a client.
     */
    public function onConnect($peer) {
        parent::onConnect($peer);
        $log = App::app()->chatGetLog($this->lptr);
        foreach($log as $msg) {
            $this->write("{$msg}\n");
        }
    }
    /**
     * onData is what our main application calls when the socket has received
     * data.
     */
    public function onData($data) {
        App::app()->chatPushLog($this->peer,$data);
    }
    /**
     * onTick is called once every loop and is responsible for dispatching the
     * message to the clients as they are received.
     */
    public function onTick() {
        $log = App::app()->chatGetLog($this->lptr);
        foreach($log as $msg) {
            $this->write("{$msg}\n");
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
        $server = new SocketServer("tcp://127.0.0.1:8081", "\\ServerSocketClass");
        echo "Server running. Now go ahead and connect to 127.0.0.1:8081 with netcat (nc 127.0.0.1 8081)\n";
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

    function client() {
        $sock = new Socket("tcp://127.0.0.1:8080");
        $sock->write("hello world from socket class\n");
        while (true) {
            echo ".";
            $d = $sock->read(1024,true);
            echo $d;
            usleep(500000);
        }
        $sock->disconnect();
        unset($sock);

        $s2 = stream_socket_client("tcp://127.0.0.1:8080");
        fwrite($s2,"hello world from stream_socket_client\n");
        fclose($s2);

    }

    function main() {

        $this->server();
        //$this->client();

    }
}

App::run(new TestApp());
