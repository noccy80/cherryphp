<?php

namespace Cherry\Proc;

class NamedPipe {
    
    private
        $file = null,
        $creator = false,
        $fh = null;
        
    public function __construct($file,$create=true) {
        $this->file = $file;
        if ((!$this->exists()) && ($create)) {
            touch($this->file);
            posix_mkfifo($this->file,0666);
            $this->creator = true;
            $this->fh = fopen($this->file,"r+");
        } else {
            if ($this->exists()) {
                $this->fh = fopen($this->file,"r+");
            } else {
                throw new \Exception("Could not open FIFO for reading: Node does not exist.");
            }
        }
        stream_set_blocking($this->fh,0);
    }
    
    public function write($data) {
        fwrite($this->fh, $data);
    }
    
    public function read() {
        $data = fread($this->fh);
        return $data;
    }
    
    public function __destruct() {
        fclose($this->fh);
        if ($this->creator) {
            unlink($this->file);
        }
    }
    
}