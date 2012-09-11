<?php

namespace Data {

    class DataBlob {
        private $data = array();
        function __construct(array $data) {
            $this->data = $data;
        }
        function __get($k) {
            if (!empty($this->data[$k]))
                return $this->data[$k];
            return null;
        }
        function __set($k,$v) {
            $this->data[$k] = $v;
        }
        function __unset($k) {
            if (!empty($this->data[$k]))
                unset($this->data[$k]);
        }
    }

}
