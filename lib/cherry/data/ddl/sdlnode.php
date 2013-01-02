<?php

namespace Cherry\Data\Ddl;

use ArrayAccess;
use Countable;

/**
 * @brief SDL (Simple Declarative Language) node implementation.
 *
 * This class covers both serializing (encoding) and unserializing (decoding)
 * of data in SDL format.
 *
 * The unserializing is built on top of the PHP tokenizer (token_get_all) and
 * is thus fast and reliable.
 *
 * @author Christopher Vagnetoft <noccylabs-at-gmail>
 * @license GNU GPL v3
 */
class SdlNode implements ArrayAccess, Countable {

    // Literal types
    const   LT_STRING   = 1; // "string" or `string`
    const   LT_CHAR     = 2; // Character as 'c'
    const   LT_INT      = 3; // 123
    const   LT_LONGINT  = 4; // 123L or 123l
    const   LT_FLOAT    = 5; // 123.45F or 123.45f
    const   LT_DFLOAT   = 6; // 123.45 or 123.45d or 123.45D
    const   LT_DECIMAL  = 7; // 123.45BD or 123.45bd
    const   LT_BOOLEAN  = 8; // Boolean, yes no or true false
    const   LT_DATE     = 9; // YYYY/MM/DD
    const   LT_DATETIME = 10; // yyyy/mm/dd hh:mm(:ss)(.xxx)(-ZONE)
    const   LT_TIMESPAN = 11; //  (d'd':)hh:mm:ss(.xxx)
    const   LT_BINARY   = 12; // [base64data]
    const   LT_NULL     = 13; // null
    // States for parser
    const   SP_NODENAME  = 0; // Expecting node name
    const   SP_NODEVALUE = 1; // Expecting node value
    const   SP_VALUELIST = 2; // We are in a value list
    const   SP_ATTRIBUTE = 3; // We are assigning to an attribute

    private $name       = null;
    private $values     = null;
    private $attr       = [];
    private $children   = [];
    private $comment    = null;

    /**
     *
     *
     */
    public function __construct($name, $values = null, array $attr = null, array $children = null, $comment = null) {
        $this->name = $name;
        $this->values = (array)$values;
        $this->attr = (array)$attr;
        $this->children = (array)$children;
        $this->comment = $comment;
    }

    /**
     * @brief Decode a string into the node.
     *
     * $nod = (new SdlNode("root"))->decode($str);
     *
     */
    public function decode($string) {
        if (!is_array($string)) {
            $subnodes = [];
            $depth = 0;
            // Opening tag required for the parser to do it's thing.
            $toks = token_get_all("<?php {$string}");
        } else {
            $toks = $string;
        }

        // Local state for parser
        $_attrn = null;
        $_attr = [];
        $_name = null;
        $_vals = [];
        $_comment = null;
        $_final = false;
        $_recurse = false;
        $_ret = false;
        $idx = 0;
        $state = self::SP_NODENAME;

        // Go over all the tokens
        while (count($toks)>0) {
            $tok = array_shift($toks);
            if (is_array($tok)) {
                $str = $tok[1];
                switch($tok[0]) {
                    // Ignore open tag
                    case T_OPEN_TAG:
                        break;

                    // Keywords, let these slip through as strings
                    case T_DEFAULT:
                    case T_CLASS:
                    case T_INTERFACE:
                    case T_ISSET:
                    case T_NAMESPACE:
                    case T_NEW:
                    case T_ECHO:
                    // Strings as keywords are handled here
                    case T_STRING:
                        if ($state == self::SP_NODENAME) {
                            // If we are expecting the node name, we got it
                            $_name = $str;
                            $state = self::SP_NODEVALUE;
                        } elseif ($state == self::SP_NODEVALUE) {
                            // If we are expecting a node value, this must be
                            // an attribute or a reserved keyword.
                            switch($str) {
                                case "null":
                                    $_vals[] = "@NULL";
                                    $idx++;
                                    break;
                                case "true":
                                case "yes":
                                    $_vals[] = true;
                                    $idx++;
                                    break;
                                case "false":
                                case "no":
                                    $_vals[] = false;
                                    $idx++;
                                    break;
                                default:
                                    $_attrn = $str;
                                    $state = self::SP_ATTRIBUTE;
                            }
                        } elseif ($state == self::SP_ATTRIBUTE) {
                            // This should never happen if not for null or consts
                            switch($str) {
                                case "null":
                                    $_attt[$_attrn] = self::LT_NULL;
                                    $_attr[$_attrn] = $str;
                                    break;
                                case "true":
                                case "yes":
                                    $_attt[$_attrn] = self::LT_BOOLEAN;
                                    $_attr[$_attrn] = "true";
                                    break;
                                case "false":
                                case "no":
                                    $_attt[$_attrn] = self::LT_BOOLEAN;
                                    $_attr[$_attrn] = "false";
                                    break;
                                default:
                                    echo "Unknown value string: ".$str."\n";
                            }
                            $state = self::SP_NODEVALUE;
                        } else {

                        }
                        break;

                    // Strings and numbers
                    case T_CONSTANT_ENCAPSED_STRING:
                        $str = trim($str,"\"");
                    case T_LNUMBER:
                        if ($state == self::SP_NODENAME) {
                            $_vals[] = $str;
                            $state = self::SP_NODEVALUE;
                            //echo str_repeat(" ",($depth+1)*4)."(value list)\n";
                        } elseif ($state == self::SP_NODEVALUE) {
                            $_vals[] = $str;
                            $idx++;
                        } elseif ($state == self::SP_ATTRIBUTE) {
                            $_attr[$_attrn] = $str;
                            $state = self::SP_NODEVALUE;
                            //echo $str."\n";
                        } else {

                        }
                        break;

                    case T_WHITESPACE:
                        if (strpos($str, "\n")!==false) {
                            $_final = true;
                            $state = self::SP_NODENAME;
                            $idx = 0;
                        }
                        break;
                    case T_COMMENT:
                        $str = trim(substr($str,3));
                        if ($_comment) $comment.="\n".$str;
                        else $_comment = $str;
                        break;
                    default:
                        throw new \UnexpectedValueException("Unhandled token in sdl: {$tok[1]} (line {$tok[2]}");
                }
            } else {
                switch($tok) {
                    case "{":
                        //$depth++;
                        //if ($state == self::SP_NODEVALUE) echo "\n";
                        $_final = true;
                        $_recurse = true;
                        $state = self::SP_NODENAME;
                        $idx = 0;
                        break;
                    case "}":
                        $_ret = true;
                        //$depth--;
                        //$state = self::SP_NODENAME;
                        //$idx = 0;
                        break;
                    case "=":
                        //echo "= ";
                        // Check keyword type and prepare for value
                        break;
                    default:
                        throw new \UnexpectedValueException("Unhandled string in sdl: {$tok}");
                }
            }
            if ($_final) {
                if ($_name || count($_vals)>0) {
                    $cnod = new SdlNode($_name,$_vals,$_attr,null,$_comment);
                    if ($_recurse) $toks = $cnod->decode($toks);
                    $this->children[] = $cnod;
                    $_comment = null;
                }
                $_name = null; $_vals = []; $_attr = [];
                $_final = false; $_recurse = false;
            }
            if ($_ret) { break; }
        }
        if (is_array($string)) {
            return $toks;
        }
    }

    /**
     *
     */
    public function encode($indent=0) {
        $ind = str_repeat(" ",$indent*4);
        $node = "";
        if ($this->comment) {
            $lines = explode("\n",$this->comment);
            foreach($lines as $line)
                $node.= $ind."// ".$line."\n";
        }
        $node.= $ind.$this->name;
        if (count($this->values)>0) {
            foreach($this->values as $value) {
                $node.=" ".$this->escape($value);
            }
        }
        if (count($this->attr)>0) {
            foreach($this->attr as $k=>$v) {
                $v = $this->escape($v);
                $node.=" {$k}={$v}";
            }
        }
        if ((count($this->children)>0) || (count($this->values)==0) ) {
            if (count($this->children)==0) {
                $node.= " { }";
            } else {
                $node.=" {\n";
                foreach($this->children as $child) {
                    $node.=$child->encode($indent+1)."\n";
                }
                $node.=$ind."}";
            }
        }
        if ($indent==0) $node.="\n";
        return $node;
    }

    /**
     *
     *
     */
    private function escape($str) {
        if (is_numeric($str)) {
            return $str;
        } elseif (is_bool($str)) {
            return ($str?'true':'false');
        } elseif ($str=="@NULL") {
            return "null";
        }
        return "\"".str_replace("\"","\\\"",$str)."\"";
    }

    /**
     *
     *
     */
    public function addChild(SdlNode $node) {
        $this->children[] = $node;
    }

    public function getName() {
        return $this->name;
    }
    public function setName($name) {
        $this->name = $name;
    }

    /**
     *
     *
     */
    public function getValues() {
        return $this->values;
    }

    public function getChildren() {
        return $this->children;
    }

    /**
     *
     *
     */
    public function getChildrenByName($name) {
        $ret = [];
        foreach($this->children as $nod) {
            if ($nod->name == $name) $ret[] = $nod;
        }
        return $ret;
        // Return all nodes of type $name
    }

    public function getChild($name) {
        foreach($this->children as $nod) {
            if ($nod->name == $name) return $nod;
        }
        return null;
    }

    /**
     *
     *
     */
    public function setComment($str) {
        $this->comment = $str;
    }

    /**
     *
     *
     */
    public function getComment() {
        return $this->comment;
    }

    /**
     *
     *
     */
    public function getAllAttributes() {
        return $this->attr;
    }

    /**
     *
     *
     */
    public function getAttribute($name) {
        if (array_key_exists($name,$this->attr))
            return $this->attr[$name];
        return null;
    }

    /**
     *
     *
     */
    public function setAttribute($name,$value) {
        $this->attr[$name] = $value;
    }

    // From countable
    public function count() {
        return count($this->values);
    }

    // From arrayaccess
    public function offsetGet($index) {
        if (isset($this->values[(int)$index]))
            return $this->values[(int)$index];
        return null;
    }
    public function offsetSet($index,$value) {
        $this->values[(int)$index] = $value;
    }
    public function offsetUnset($index) {
        if (isset($this->values[(int)$index]))
            unset($this->values[(int)$index]);
    }
    public function offsetExists($index) {
        return (isset($this->values[(int)$index]));
    }

    public function __get($key) {
        if (array_key_exists($key,$this->attr))
            return $this->attr[$key];
        return null;
    }

    public function __set($key,$val) {
        $this->attr[$key]=$val;
    }

    public function __unset($key) {
        unset($this->attr[$key]);
    }

}
