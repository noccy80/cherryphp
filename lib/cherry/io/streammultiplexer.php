<?php

namespace Cherry\Io;

class StreamMultiplexer {
    
    private $streams = array();
    private $sread = array();
    private $swrite = array();
    private $sexcept = array();
    private $timeoutns = null;
    private $timeout = null;
    private $timeoutu = null;
    
    public function __construct(array $streams = null, $timeout = null) {
        $this->streams = (array)$streams;
        $this->setTimeout($timeout);
    }
    
    public function setTimeout($timeout) {
        $this->timeout = 0;
        $this->timeoutu = $timeout;
    }
    
    public function addStream(\Stream $stream, $streamid = null) {
        $this->streams[$streamid] = $stream;
    }
    
    public function removeStream($streamid) {
        unset($this->streams[$streamid]);
    }
    
    public function select() {
        $sread = $this->streams; $swrite = $this->streams; $sexcept = $this->streams;
        $this->changed = stream_select($sread,$swrite,$sexcept,$this->timeout,$this->timeoutu);
        $this->sread = $sread; $this->swrite = $swrite; $this->sexcept = $sexcept;
        return array($sread, $swrite, $sexcept);
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
    
    public function getStreams() {
        return $this->streams;
    }

}