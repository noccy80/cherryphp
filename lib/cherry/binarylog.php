<?php

namespace Cherry;

class BinaryLog {

    private $hlog = null;
    private $header = null;
    private $eof = false;
    private $headerfunc = null;

    ///@var Frame continues in next frame (partial)
    const SL_FLAG_CONTINUED = 0x01;
    ///@var Frame has header (with data)
    const SL_FLAG_HEADER = 0x02;
    ///@var Frame has CRC32 appended.
    const SL_FLAG_CRC32 = 0x04;

    /**
     * @brief Constructor
     *
     * The mode flag is borrowed from fopen, where W is write and A is append. R is used for reading only.
     */
    public function __construct($filename,$mode) {
        $mode = strtolower($mode);
        switch($mode[0]) {
            case 'w':
            case 'a':
                $this->can_write = true;
                $this->can_read = false;
                $this->append = ($mode[0] == "a");
                break;
            case 'r':
                $this->can_write = false;
                $this->can_read = true;
                $this->append = false;
                break;
            default:
                throw new \UnexpectedArgumentException();
        }
        $this->hlog = fopen($filename,$mode);
    }

    public function __destruct() {
        if ($this->hlog) {
            fclose($this->hlog);
            $this->hlog = null;
        }
    }

    public function isEof() {
        return $this->eof;
    }

    public function setHeaderFunc(callable $func,$push=false) {
        if ($func == null) {
            if (is_array($this->headerfunc)) {
                array_shift($this->headerfunc);
                return;
            } else {
                $this->headerfunc = null;
            }
        }
        if ($push) {
            if (!is_array($this->headerfunc))
                $this->headerfunc = [];
            array_unshift($this->headerfunc,$func);
        } else {
            $this->headerfunc = $func;
        }
    }

    public function write($data,$header=null) {
        $flags = 0x00;
        if (!empty($header)) {
            $flags |= self::SL_FLAG_HEADER;
            $data = [ $header, $data ];
        } elseif (!empty($this->headerfunc)) {
            if (is_array($this->headerfunc))
                $header = call_user_func($this->headerfunc[0],$data);
            else
                $header = call_user_func($this->headerfunc,$data);
        }
        $ser = serialize($data);
        $len = strlen($ser);
        // 2 byte length, 1 byte flags. then data
        $out = pack("sCa{$len}", $len, $flags, $ser);
        fwrite($this->hlog,$out);
    }

    public function getHeader() {
        return $this->header;
    }

    public function read(&$header) {
        $hdr = fread($this->hlog,3);
        if ($hdr) {
            $hdr = unpack("ssize/Cflags", $hdr);
            $str = fread($this->hlog,$hdr['size']);
            $data = unserialize($str);
            if (($hdr['flags'] & self::SL_FLAG_HEADER)) {
                $this->header = $data[0];
                $data = $data[1];
            } else {
                $this->header = null;
            }
            $header = $this->header;
            return $data;
        } else {
            $this->header = null;
            $header = null;
            $this->eof = true;
            return null;
        }
    }
}
