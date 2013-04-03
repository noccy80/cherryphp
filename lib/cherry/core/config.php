<?php

namespace Cherry;

class ConfigurationFile {

    const CFG_JSON = 1;
    const CFG_INI = 2;
    const CFG_YAML = 3;
    const CFG_XML = 4;

    private $data = array();
    private $format = null;
    private $filename = null;

    public function __construct($file) {
        
        $this->filename = $file;
        if (fnmatch("*.json",$file)) {
            $this->format = self::CFG_JSON;
            $this->data = self::objectToArray(json_decode($file));
        } elseif (fnmatch("*.ini",$file)) {
            $this->format = self::CFG_INI;
            if (!file_exists($file)) {
                $this->data = array();
            } else {
                $this->data = parse_ini_file($file,true);
            }
        } elseif (fnmatch("*.yaml",$file)) {
            $this->format = self::CFG_YAML;
            $this->data = yaml_parse($file);
        } elseif (fnmatch("*.xml",$file)) {
            $this->data = self::objectToArray(simplexml_load_file);
        }
        
    }
    
    public function getValue($key)
    {
    
        $keys = explode('/',$key);
        $ds = $this->data;
        foreach($keys as $subkey) {
            if ($subkey) {
                if (array_key_exists($subkey,$ds)) {
                    $ds = $ds[$subkey];
                } else {
                    return null;
                }
            }
        }
        return $ds;
        
    }
    
    public function setValue($key,$value,$create=true)
    {
        
        $keys = explode('/',$key);
        $ds = $this->data;
        foreach($keys as $subkey) {
            if ($subkey) {
                if ($subkey == end($keys)) {
                    $ds[$subkey] = $value;
                    return true;
                }
                if (!array_key_exists($subkey,$ds)) {
                    if (!$create) return null;
                    $ds[$subkey] = array();
                } 
                $ds = &$ds[$subkey];
            }
        }
        var_dump($this->data);
        
    }
    
    public function save() {

        switch($this->format) {
            case self::CFG_INI:
                $array = $this->data;
                $res = array();
                foreach($array as $key => $val)
                {
                    if(is_array($val))
                    {
                        $res[] = "[$key]";
                        foreach($val as $skey => $sval) $res[] = "$skey = ".(is_numeric($sval) ? $sval : '"'.$sval.'"');
                    }
                    else $res[] = "$key = ".(is_numeric($val) ? $val : '"'.$val.'"');
                }
                break;
            case self::CFG_JSON:
                break;
            case self::CFG_YAML:
                break;
            default:
                // Error
        }
        self::safefilerewrite($this->filename, join("\n", $res));
        
        
    }

    static public function objectToArray($obj)
    {
        $arr = (is_object($obj))?
            get_object_vars($obj) :
            $obj;

        foreach ($arr as $key => $val) {
            $arr[$key] = ((is_array($val)) || (is_object($val)))?
                self::objectToArray($val) :
                $val;
        }
        
        return $arr;
    }

    function safefilerewrite($fileName, $dataToSave)
    {
        if ($fp = fopen($fileName, 'w')) {
            $startTime = microtime();
            do {
                $canWrite = flock($fp, LOCK_EX);
                // If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
                if(!$canWrite) usleep(round(rand(0, 100)*1000));
            } while ((!$canWrite)and((microtime()-$startTime) < 1000));
    
            //file was locked so now we can store information
            if ($canWrite) {
                fwrite($fp, $dataToSave);
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }
    
    }
}

