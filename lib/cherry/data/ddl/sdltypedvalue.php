<?php

namespace Cherry\Data\Ddl;

class SdlTypedValue {
    // Literal types - U = unsupported, P = partial support
    const   LT_STRING   = 1;  //     "string" or `string`
    const   LT_CHAR     = 2;  // [U] Character as 'c'
    const   LT_INT      = 3;  //     123
    const   LT_LONGINT  = 4;  // [U] 123L or 123l
    const   LT_FLOAT    = 5;  // [U] 123.45F or 123.45f
    const   LT_DFLOAT   = 6;  // [P] 123.45 or 123.45d or 123.45D
    const   LT_DECIMAL  = 7;  // [U] 123.45BD or 123.45bd
    const   LT_BOOLEAN  = 8;  //     Boolean, yes no or true false
    const   LT_DATE     = 9;  // [U] YYYY/MM/DD
    const   LT_DATETIME = 10; // [U] yyyy/mm/dd hh:mm(:ss)(.xxx)(-ZONE)
    const   LT_TIMESPAN = 11; // [U] (d'd':)hh:mm:ss(.xxx)
    const   LT_BINARY   = 12; //     [base64data]
    const   LT_NULL     = 13; //     null

    // Keywords to expand
    private static $kwexpand = [
        "true" => true,
        "yes" => true,
        "on" => true,
        "false" => false,
        "off" => false,
        "no" => false,
        "null" => null
    ];

    private $type;
    private $value;
    private $source;

    public function __construct($value,$type=null,$source=null) {
        $this->value = $value;
        $this->source = $source;
        if ($type)
            $this->type = $type;
        else
            $this->type = $this->detectType($value);
    }

    public function __toString() {
        return "<".$this->value.">";
    }

    const RE_STRING     = "/^\"(.*)\"$/m";
    const RE_CHAR       = "/^\'.\'$/";
    const RE_INT        = "/^[\+\-]{0,1}[0-9]*$/";
    const RE_LONGINT    = "/^[\+\-]{0,1}[\.0-9]*[l]?$/i";
    const RE_FLOAT      = "/^[\+\-]{0,1}[\.0-9]*[f]?$/i";
    const RE_DFLOAT     = "/^[\+\-]{0,1}[\.0-9]*[d]?$/i";
    const RE_DATETIME   = "/^([0-9]{4})\/([0-9]{2})\/([0-9]{2}) ([0-9]{2}):([0-9]{2})(:[0-9]{2})([\.]{0,1}[0-9]*)([\-]{0,1}.*)$/";
    const RE_DATE       = "/^([0-9]{4})\/([0-9]{2})\/([0-9]{2})$/";

    public function getValue() {
        return $this->value;
    }

    /**
     * Change the value while preserving the type if possible.
     */
    public function setValue($value) {
        switch($this->type) {
            case self::LT_DECIMAL:
            case self::LT_DFLOAT:
            case self::LT_FLOAT:
                if (is_float($value)) {
                    $this->value = $value;
                    return;
                }
                break;
            case self::LT_INT:
            case self::LT_LONGINT:
                if (is_int($value)) {
                    $this->value = $value;
                    return;
                }
                break;
            case self::LT_BOOLEAN:
                if (is_bool($value)) {
                    $this->value = $value;
                    return;
                }
                break;
            case self::LT_BINARY:
                $this->value = $value;
                return;
        }
        $this->value = $value;
        if (is_float($value)) {
            $this->type = self::LT_FLOAT;
        } elseif (is_bool($value)) {
            $this->type = self::LT_BOOLEAN;
        } else {
            $this->type = self::LT_STRING;
        }

    }

    public static function parse($value) {
        // TODO: Pay attention to quoting and parse non-quoted tokens
        //echo "[Parsing value string '{$value}']\n";
        if (preg_match(self::RE_STRING, $value)) {
            return new self(substr($value,1,strlen($value)-2), self::LT_STRING, $value);
        } elseif (preg_match(self::RE_CHAR, $value)) {
            return new self(substr($value,1,strlen($value)-2), self::LT_CHAR, $value);
        } elseif (preg_match(self::RE_FLOAT, $value)) {
            return new self(floatval($value), self::LT_FLOAT, $value);
        } elseif (preg_match(self::RE_DFLOAT, $value)) {
            return new self(floatval($value), self::LT_DFLOAT, $value);
        } elseif (preg_match(self::RE_INT, $value)) {
            return new self(intval($value), self::LT_INT, $value);
        } elseif (preg_match(self::RE_LONGINT, $value)) {
            return new self(intval($value), self::LT_LONGINT, $value);
        } elseif (preg_match(self::RE_DATETIME, $value)) {
            $match = null;
            preg_match_all(self::RE_DATETIME, $value, $match);
            list($year,$month,$day) = [$match[1][0],$match[2][0],$match[3][0]];
            list($hour,$minute,$second) = [$match[4][0],$match[5][0],$match[6][0]];
            if ($second[0] != ":") {
                if ($second[0] == ".") {
                    $micro = floatval($second);
                } elseif ($second[0] == "-") {
                    $tz = substr($second,1);
                }
                $second = 0;
            } else {
                $second = substr($second,1);
                $micro = $match[7][0];
                if ($micro[0] != ".") {
                    $tz = substr($micro,1);
                    $micro = 0;
                } else {
                    $tz = substr($match[8][0],1);
                }
            }
            if ($tz && (!defined("SDL_IGNORE_TIMEZONE"))) {
                throw new SdlParserException("Timezones for dates are not implemented. Define SDL_IGNORE_TIMEZONE to disable this exception.", SdlParserException::ERR_NOT_IMPLEMENTED);
            }
            // TODO: Implement timezones
            $ts = mktime($hour,$minute,$second,$month,$day,$year); //
            $ts+= $micro;
            return new SdlTypedValue($ts, self::LT_DATETIME, $value);
        } elseif (preg_match(self::RE_DATE, $value)) {
            $match = null;
            preg_match_all(self::RE_DATE, $value, $match);
        } elseif (array_key_exists($value,self::$kwexpand)) {
            $value = self::$kwexpand[$value];
            if (is_bool($value)) {
                return new SdlTypedValue($value,self::LT_BOOLEAN, $value);
            } elseif (is_null($value)) {
                return new SdlTypedValue($value,self::LT_NULL, $value);
            }
        } else {
            return new SdlTypedValue($value,self::LT_STRING, $value);
            //fprintf(STDERR,"Warning: Value type could not be determined for '{$value}'\n");
        }
    }

    public function encode() {
        return $this->source;
    }

}
