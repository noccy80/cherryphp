<?php

namespace Cherry\Data;

use Cherry\Database\DatabaseConnection;

abstract class Model {

    private $data = [];
    private $dptr = 0;

    protected function call($mtd,$args) {
        $out = "";
        for($n = 0; $n < strlen($mtd); $n++) {
            if (ctype_upper($mtd[$n]))
                $out.= " ".strtolower($mtd[$n]);
            else
                $out.= $mtd[$n];
        }
        $toks = explode(" ",$out);
        $value = $args[0];
        if ($toks[0] == "find") {
            if (count($toks)==1) {
                return $this->findRecord($this->primary,$value);
            } elseif (($toks[1] == "by") && (count($toks)==3)) {
                return $this->findRecord($toks[2],$value);
            }
        }
        echo $out."\n";
    }

    private function findRecord($column,$value) {
        $db = DatabaseConnection::getInstance();
        $sql = "SELECT * FROM {$this->table} WHERE `{$column}`=%s";
        $this->data = ($db->query($sql,$value)->fetchAll());
        return $this;
    }

    public function __get($key) {
        if (array_key_exists($key,$this->data[$this->dptr])) {
            $val = $this->data[$this->dptr][$key];
            $type = $this->columns[$key];
            if (strpos($type,":")) {
                list($type,$length) = explode(":",$type,2);
            } else $length = null;
            if ($type == "date") {
                $time = strtotime($val);
                if ($time)
                    return date(\DateTime::RFC822,$time);
                else
                    return null;
            } elseif ($type == "bool")
                return ($val==0)?false:true;
            else
                return $val;
        }
        return null;
    }
    public function __set($key,$value) {
        if (array_key_exists($key,$this->data[$this->dptr]))
            $this->data[$this->dptr][$key] = $value;
        else
            throw new \RuntimeException("Invalid key requested: {$key}");
    }

    public function setValidator($attribute,$validator) { }
    public function getValidator() { }

}
