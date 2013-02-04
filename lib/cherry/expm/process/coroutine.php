<?php

namespace Cherry\Expm\Process;

/**
 *
 * This class does not implement a coroutine in the strictest sense of the name
 * but rather contains a threaded coroutine with communication via fifo queues.
 * Messages can be passed in both directions, and
 *
 */
class CoRoutine {
    private $routine = null;
    private $ipc_copi = null;
    private $ipc_poci = null;
    private $ipc_copif = null;
    private $ipc_pocif = null;
    private $childpid = null;
    public $onmessage = null;
    public function __construct(callable $routine = null) {
        $this->ipc_copi = null;
        $this->ipc_poci = null;
        $this->routine = $routine;
    }
    public function __destruct() {
        if ($this->childpid) {
            $status = 0;
            \debug("Waiting for child {$this->childpid}");
            pcntl_waitpid($this->childpid,$status,\WUNTRACED);
            \debug("Child terminated {$this->childpid}");
            // Close fifo in parent
            @fclose($this->ipc_copi);
            @fclose($this->ipc_poci);
            // Unlink fifos
            @unlink($this->ipc_copif);
            @unlink($this->ipc_pocif);
        } else {
            \debug("Child destructing");
        }
    }
    public function start() {
        $args = func_get_args();
        // Create our IPC placeholders
        $this->ipc_copif = \tempnam(null,"corti");
        $this->ipc_pocif = \tempnam(null,"corto");
        \posix_mkfifo($this->ipc_copif,0600);
        \posix_mkfifo($this->ipc_pocif,0600);

        // Fork the process, get PID in parent, 0 in child, -1 on error.
        $pid = pcntl_fork();
        if ($pid == 0) {
            while(!file_exists($this->ipc_copif)) {
                echo "x";
                usleep(10000); clearstatcache(false,$this->ipc_copif);
            }
            $this->ipc_copi = fopen($this->ipc_copif,"w");
            while(!file_exists($this->ipc_pocif)) {
                echo "y";
                usleep(10000); clearstatcache(false,$this->ipc_pocif);
            }
            $this->ipc_poci = fopen($this->ipc_pocif,"r+");
            if (is_callable([$this,"main"])) {
                 call_user_func_array([$this,"main"],$args);
            } else {
                if (!$this->routine)
                    user_error("No CoRoutine assigned!");
                if ($this->routine instanceof \Closure) {
                    \debug("Assigning scope of coroutine");
                    $call = $this->routine->bindTo($this,$this);
                }
                call_user_func_array($call,$args);
            }
            // Close fifo in child
            fclose($this->ipc_copi);
            fclose($this->ipc_poci);
            exit;
        }
        if ($pid == -1) {
            // error
            return false;
        }
        // Set up the IPC
        while(!file_exists($this->ipc_copif)) {
            echo "a";
            usleep(10000); clearstatcache(false,$this->ipc_copif);
        }
        $this->ipc_copi = fopen($this->ipc_copif,"r+");
        while(!file_exists($this->ipc_pocif)) {
            echo "b";
            usleep(10000); clearstatcache(false,$this->ipc_pocif);
        }
        $this->ipc_poci = fopen($this->ipc_pocif,"w");
        $this->childpid = $pid;
        return true;
    }
    public function stop() {
        if ($this->childpid) {
            \posix_kill($this->childpid,SIGKILL);
        }
    }
    public function isRunning() {
        if ($this->childpid) {
            $pidpath = "/proc/{$this->childpid}/cmdline";
            clearstatcache(false,$pidpath);
            return (is_readable($pidpath));
        }
    }
    public function sendMessage($message) {
        $message = serialize($message);
        $message = pack("S",strlen($message)).$message;
        if ($this->childpid == 0) {
            fwrite($this->ipc_copi,$message,strlen($message));
        } else {
            fwrite($this->ipc_poci,$message,strlen($message));
        }
    }
    public function pollMessage() {
        static $buf = null;
        $ret = null;
        if ($this->childpid == 0) {
            stream_set_blocking($this->ipc_poci,false);
            $read = fread($this->ipc_poci,1024);
        } else {
            stream_set_blocking($this->ipc_copi,false);
            $read = fread($this->ipc_copi,1024);
        }
        if ($read) {
            $buf.=$read;
        }
        if (strlen($buf)>3) {
            $size = unpack("S",substr($buf,0,2))[1];
            $bs = strlen($buf);
            if (strlen($buf) > $size) {
                // Should be if data + 2 bytes of header is in there
                $data = substr($buf,2,$size);
                $buf = substr($buf,$size+2);
                $ret = unserialize($data);
            } else {
                // Nope, not enough data.
                $ret = null;
            }
        }
        return $ret;
    }
}
