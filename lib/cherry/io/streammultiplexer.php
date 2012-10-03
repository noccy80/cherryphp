<?php

namespace Cherry\Io;

class StreamMultiplexer {
    
    private $streams = array();
    private $sread = array();
    private $swrite = array();
    private $sexcept = array();
    private $timeoutns = null;
    private $timeout = 0;
    private $timeoutu = 0;
    
    public function __construct(array $streams = null, $timeout = null) {
        $this->streams = (array)$streams;
        $this->setTimeout($timeout);
    }
    
    public function setTimeout($timeout) {
        $this->timeout = 0;
        $this->timeoutu = $timeout;
    }
    
    public function addStream($stream, $streamid = null) {
        if (!$streamid) $streamid = uniqid('smpx');
        $this->streams[$streamid] = $stream;
    }
    
    public function removeStream($streamid) {
        unset($this->streams[$streamid]);
    }
    
    public function select() {
        /* // NOTE: This part doesn't work for some weird reason involving filters.
        $sread = array_values($this->streams); $swrite = $sread; $sexcept = $sread;
        $this->changed = stream_select($sread,$swrite,$sexcept,$this->timeout,$this->timeoutu);
        $this->sread = $sread; $this->swrite = $swrite; $this->sexcept = $sexcept;
        */
        $sread = array();
        $swrite = array();
        $sexcept = array();
        $mod = false;
        foreach($this->streams as $streamid=>$stream) {
            $md = stream_get_meta_data($stream);
            if ($md['unread_bytes'] > 0) {
                $sread[$streamid] = $stream;
                $mod = true;
            }
        }
        $this->sread = $sread; $this->swrite = $swrite; $this->sexcept = $sexcept;
        return $mod;
    }
    
    public function getReadableStreams() {
        return $this->sread;
    }
    
    public function getWritableStreams() {
        return $this->swrite;
    }
    
    public function getExceptStreams() {
        return $this->sexcept;
    }
    
    public function isStreamReadable($streamid) {
        $so = $this->getStream($streamid);
        return (in_array($so,$this->sread));
    }
    
    public function isStreamWritable($streamid) {
        $so = $this->getStream($streamid);
        return (in_array($so,$this->swrite));
    }
    
    public function isStreamExcept($streamid) {
        $so = $this->getStream($streamid);
        return (in_array($so,$this->sexcept));
    }
    
    public function getStream($streamid) {
        return $this->streams[$streamid];
    }
    
    public function getAllStreams() {
        return $this->streams;
    }

}
