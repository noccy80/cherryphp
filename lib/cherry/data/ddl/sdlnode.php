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
 * @todo
 *   - Implement the remaining types.
 *   - Attributes should also support namespaces
 *   - Multiline strings with "\"
 *   - Use ; to separate tags, as per SDL 1.1
 *
 * @author Christopher Vagnetoft <noccylabs-at-gmail>
 * @license GNU GPL v3
 */
class SdlNode implements ArrayAccess, Countable {

    // Literal types
    const   LT_STRING   = 1; // "string" or `string`
    const   LT_CHAR     = 2; // Character as 'c'   --- UNSUPPORTED
    const   LT_INT      = 3; // 123
    const   LT_LONGINT  = 4; // 123L or 123l   --- UNSUPPORTED
    const   LT_FLOAT    = 5; // 123.45F or 123.45f   --- UNSUPPORTED
    const   LT_DFLOAT   = 6; // 123.45 or 123.45d or 123.45D   --- PARTIALLY
    const   LT_DECIMAL  = 7; // 123.45BD or 123.45bd   --- UNSUPPORTED
    const   LT_BOOLEAN  = 8; // Boolean, yes no or true false
    const   LT_DATE     = 9; // YYYY/MM/DD   --- UNSUPPORTED
    const   LT_DATETIME = 10; // yyyy/mm/dd hh:mm(:ss)(.xxx)(-ZONE)   --- UNSUPPORTED
    const   LT_TIMESPAN = 11; //  (d'd':)hh:mm:ss(.xxx)   --- UNSUPPORTED
    const   LT_BINARY   = 12; // [base64data]   --- UNSUPPORTED
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
    private $doccomment = null;
    private $ns         = null;

    /**
     * @brief Create a new SDL node
     *
     * @param string $name The node name (with optional prefixed namespace followed by :, eg. foo:bar)
     * @param array|string $values The value(s) of the node. Can be null.
     * @param array $attr The attributes to attach to the node.
     * @param array $children The child nodes that belong to this node.
     * @param string $comment A textual description of the node. The comment will be serialized.
     */
    public function __construct($name = null, $values = null, array $attr = null, array $children = null, $comment = null) {
        if (strpos($name,':')!==false) {
            list($this->ns,$this->name) = explode(':',$name,2);
        } else {
            $this->name = $name;
        }
        // Extract the values as typed values
        $this->values = array_map([$this,'getSingleTypedValue'], (array)$values);
        foreach((array)$attr as $k=>$value) {
            $this->attr[$k] = $this->getSingleTypedValue($value);
        }
        $this->children = (array)$children;
        $this->comment = $comment;
    }

    /**
     * @brief Load a file as children to the current node.
     *
     * @param string $file The filename to load
     */
    public function loadFile($file) {
        // TODO: Check for errors
        $fc = file_get_contents($file);
        $this->loadString($fc);
    }

    /**
     * @brief Load a string as children to the current node.
     *
     * @param string $str The string containing SDL data to decode
     */
    public function loadString($str) {
        $this->decode($str);
    }

    /**
     * @brief DEPRECATED: Decode a string into the node.
     * @see loadString()
     *
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
        $_doccomment = null;
        $_comment = null;
        $_final = false;
        $_recurse = false;
        $_ret = false;
        $_ns = null;
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
                    case T_LOGICAL_OR:
                    case T_DEFAULT:
                    case T_CLASS:
                    case T_INTERFACE:
                    case T_EXTENDS:
                    case T_ISSET:
                    case T_NAMESPACE:
                    case T_NEW:
                    case T_ECHO:
                    case T_INCLUDE:
                    case T_IF:
                    case T_VAR:
                    case T_STATIC:
                    case T_PRINT:
                    case T_USE:
                    case T_ELSE:
                    case T_ELSEIF:
                    // And we have numbers as well
                    case T_DNUMBER:
                    // Strings as keywords are handled here
                    case T_STRING:
                        if ($state == self::SP_NODENAME) {
                            // If we are expecting the node name, we got it
                            $_name = $str;
                            $state = self::SP_NODEVALUE;
                        } elseif ($state == self::SP_NODEVALUE) {
                            // If we are expecting a node value, this must be
                            // an attribute or a reserved keyword.
                            $nvalue = null;
                            if ($this->getTypedValue($str,$tok[0],$nvalue)) {
                                $_vals[] = $nvalue;
                                $idx++;
                            } else {
                                $_attrn = $str;
                                $state = self::SP_ATTRIBUTE;
                            }
                        } elseif ($state == self::SP_ATTRIBUTE) {

                            $value = null;
                            if ($this->getTypedValue($str,$tok[0],$value)) {
                                $_attr[$_attrn] = $value;
                            } else {
                                $_attr[$_attrn] = $str;
                            }
                            $state = self::SP_NODEVALUE;
                        } else {

                        }
                        break;

                    // Strings and numbers
                    case T_CONSTANT_ENCAPSED_STRING:
                        $str = trim($str,"\"");
                        $str = str_replace("\\\"",'"',$str);
                    case T_LNUMBER:
                        if ($state == self::SP_NODENAME) {
                            if ($_ns)
                                throw new SdlParseException("Namespace declared but no node value present on line {$tok[2]}");
                            $value = null;
                            if ($this->getTypedValue($str,$tok[0],$value)) {
                                $_vals[] = $value;
                            } else {
                                $_vals[] = $str;
                            }
                            $state = self::SP_NODEVALUE;
                            //echo str_repeat(" ",($depth+1)*4)."(value list)\n";
                        } elseif ($state == self::SP_NODEVALUE) {
                            if ($this->getTypedValue($str,$tok[0],$value)) {
                                $_vals[] = $value;
                            } else {
                                $_vals[] = $str;
                            }
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
                        if ($_comment) $_comment.="\n".$str;
                        else $_comment = $str;
                        break;
                    case T_DOC_COMMENT:
                        $str = trim(substr($str,3));
                        if ($_doccomment) $_cdocomment.="\n".$str;
                        else $_doccomment = $str;
                        break;
                    default:
                        $type = token_name($tok[0]);
                        throw new SdlParseException("Unhandled token in sdl: {$tok[1]} of type {$type} (line {$tok[2]}");
                }
            } else {
                switch($tok) {
                    case "{":
                        // Parse the current tag and recurse the children
                        $_final = true;
                        $_recurse = true;
                        $state = self::SP_NODENAME;
                        $idx = 0;
                        break;
                    case "}":
                        //$_final = true;
                        $_ret = true;
                        break;
                    case ";";
                        // On semicolon we finalize the current tag and resume
                        // looking for the next node name.
                        $_final = true;
                        $state = self::SP_NODENAME;
                        break;
                    case "=":
                        // Pop the last found value as the attribute name
                        // We should make sure that it is a valid attribute
                        // here really.
                        $state = self::SP_ATTRIBUTE;
                        $_attrn = array_pop($_vals)[0];
                        break;
                    case ":":
                        // Namespace parsing. Implementation needed for attribute
                        // namespaces.
                        $_ns = $_name;
                        $_name = null;
                        $state = self::SP_NODENAME;
                        break;
                    default:
                        throw new SdlParseException("Unhandled string in sdl: {$tok}");
                }
            }
            // The final flag creates the node.
            if ($_final) {
                if ($_name || count($_vals)>0) {
                    if ($_ns) $_name = $_ns.':'.$_name; // Add namespace
                    $cnod = new SdlNode($_name,$_vals,$_attr,null,$_comment);
                    if ($_recurse) $toks = $cnod->decode($toks);
                    $this->children[] = $cnod;
                    $_comment = null;
                    $_doccomment = null;
                }
                $_name = null; $_vals = []; $_attr = [];
                $_final = false; $_recurse = false; $_ns = null;
            }
            if ($_ret) { break; }
        }
        // Return the remainder of the tokens after parsing a subtree.
        if (is_array($string)) {
            return $toks;
        }
    }

    /**
     * @brief Encode the node and all child nodes into serialized SDL
     *
     * @param $indent The level of indenting
     * @return string The SDL string
     */
    public function encode($indent=0) {
        $ind = str_repeat(" ",$indent*4);
        $node = "";
        if ($this->comment) {
            $lines = explode("\n",$this->comment);
            foreach($lines as $line)
                $node.= $ind."// ".$line."\n";
        }
        $node.= $ind;
        if ($this->ns)
            $node.= $this->ns.':';
        $node.= $this->name;
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
                //$node.= " { }";
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
     * @brief Escape strings.
     *
     * This function will escape special characters such as backslashes as well
     * as encode boolean keywords (true/false) or the "meta-null" value "@NULL"
     * into the string null.
     *
     * @param string $str The string to escape
     * @return string The escaped and quoted string
     */
    private function escape($str) {
        if (is_array($str)) {
            $type = $str[1];
            $val = $str[0];
            switch($type) {
                case self::LT_BOOLEAN:
                    return ($val?'true':'false');
                case self::LT_NULL:
                    return 'null';
                case self::LT_DFLOAT:
                    return $val;
                case self::LT_INT:
                    return $val;
                default:
                    $str = $val;
            }
        } elseif (is_numeric($str)) {
            if (!is_string($str)) return $str;
        } elseif (is_bool($str)) {
            return ($str?'true':'false');
        } elseif ($str=="@NULL") {
            return "null";
        }
        return "\"".str_replace("\"","\\\"",$str)."\"";
    }

    /**
     * @private
     * @brief Returns a typed value pair for a token.
     *
     * This method also handles the keywords to ensure that they come out
     * properly.
     *
     * @todo Throw exception on invalid keywords.
     * @todo Parse dates and other value types.
     *
     * @param string $value The value string
     * @param Mixed $tok The token identifier
     * @param Array &$typedval The resulting value
     * @return bool True if the typed value could be retrieved
     */
    private function getTypedValue($value,$tok,&$typedval) {
        if (is_array($value)) {
            $typedval = $value;
            return true;
        }
        if ($value === null) {
            $typedval = [ null, self::LT_NULL ];
            return true;
        } elseif ($value === true) {
                $typedval = [ true, self::LT_BOOLEAN ];
                return true;
        } elseif ($value === false) {
                $typedval = [ false, self::LT_BOOLEAN ];
                return true;
        }
        switch($value) {
            case "null":
                if ($tok == T_STRING) {
                    $typedval = [ null, self::LT_NULL];
                    return true;
                }
            case "true":
            case "yes":
                if ($tok == T_STRING) {
                    $typedval = [ true, self::LT_BOOLEAN ];
                    return true;
                }
            case "false":
            case "no":
                if ($tok == T_STRING) {
                    $typedval = [ false, self::LT_BOOLEAN ];
                    return true;
                }
                break;
            default:
                // TODO: Check token to make sure it's a constant encapsed string, and do
                // approproiate multi-line merging based on trailing \
                if (is_string($value)) {
                    $typedval = [ $value, self::LT_STRING ];
                    return true;
                }
                // For numbers we need some magic
                if (is_numeric($value)) {
                    if (is_integer($value)) {
                        $typedval = [ intval($value), self::LT_INT ];
                        return true;
                    }
                    $typedval = [ floatval($value), self::LT_DFLOAT ];
                    return true;
                }
        }
        return false;
    }

    /**
     * @private
     * @brief Convert a single known value into a typed value.
     *
     * This is used for assignments.
     *
     * @param Mixed $value The value
     * @return Mixed The typed value
     */
    private function getSingleTypedValue($value) {
        $ret = null;
        if ($this->getTypedValue($value,null,$ret)) {
            return $ret;
        } else {
            return $value;
        }
    }

    /**
     * @private
     * @brief Return the value in a native PHP value type.
     *
     * @param Mixed $value The value to cast
     * @return Mixed The cast value
     */
    private function getCastValue($value) {
        if (is_array($value)) {
            $type = $value[1];
            $val = $value[0];
            switch($type) {
                case self::LT_BOOLEAN:
                    return ($val?true:false);
                case self::LT_NULL:
                    return null;
                case self::LT_DFLOAT:
                    return $val;
                case self::LT_INT:
                    return $val;
                case self::LT_STRING:
                    return $val;
                default:
                    throw new SdlParseException("Internal error: Casting from unhandled internal value type.");
                    $str = $val;
            }
        }
        return $value;
    }

    /**
     * @brief Add a child node to the node.
     *
     * @param SdlNode $node The node to append
     */
    public function addChild(SdlNode $node) {
        $this->children[] = $node;
    }

    public function removeChild(SdlNode $node) {
        $this->children = array_filter(
            $this->children,
            function($nv) use ($node) {
                return (!($nv === $node));
            }
        );
    }

    /**
     * @brief Return the name of the node.
     *
     * @return string The node name
     */
    public function getName() {
        return $this->name;
    }

    public function getNameNs() {
        if ($this->ns)
            return $this->ns.':'.$this->name;
        else
            return ':'.$this->name;
    }

    /**
     * @brief Set the name of the node.
     *
     * @param string $name The name to set
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @brief Return the namespace of the node
     *
     * @return string The namespace (or null)
     */
    public function getNamespace() {
        return $this->ns;
    }

    /**
     * @brief Set the namespace of the node
     *
     * @param string $ns The namespace to set
     */
    public function setNamespace($ns) {
        $this->ns = $ns;
    }

    /**
     * @brief Set the node comment.
     *
     * You can set the comment to null to remove it.
     *
     * @param string $str The comment
     */
    public function setComment($str) {
        $this->comment = $str;
    }

    /**
     * @brief Get the node comment
     *
     * @return string The comment (or null)
     */
    public function getComment() {
        return $this->comment;
    }

    /**
     * @brief Return all the values of the node
     *
     * @return array The values
     */
    public function getValues() {
        $vo = [];
        foreach($this->values as $vl) $vo[] = $this->getCastValue($vl);
        return $vo;
        //return $this->values;
    }

    public function setValue($value) {
        $this->values[0] = $this->getSingleTypedValue($value);
    }

    public function addValue($value) {
        $this->values[] = $this->getSingleTypedValue($value);
    }

    /**
     * @brief Return the first value of the node.
     *
     * This function is useful if you don't want to use arrayaccess, i.e. $node[0]
     *
     * @return mixed The first value of the node
     */
    public function getValue() {
        return $this->getCastValue($this->values[0]);
    }

    /**
     * @brief Return all children whose node name match the string.
     *
     * @param string $name Tag name or null for all.
     * @return array The matchind nodes or null.
     */
    public function getChildren($name=null) {
        // If $name is null, return all children
        if (!$name)
            return $this->children;
        // Return all nodes of type $name
        $ret = [];
        foreach($this->children as $nod) {
            if ($nod->getName() == $name) $ret[] = $nod;
        }
        return $ret;
    }

    /**
     * @brief Return the first child whose node ame match the string.
     *
     * @param string $name The node name to match
     * @param string $withvalue The node value to match (or null)
     * @return SdlNode The first matching node or null
     */
    public function getChild($name,$withvalue=null) {
        foreach($this->children as $nod) {
            if ($nod->getName() == $name) {
                if (!$withvalue) return $nod;
                if ($withvalue == $nod->getValue()) return $nod;
            }
        }
        return null;
    }

    public function hasChildren() {
        return (count($this->children) > 0);
    }

    /**
     * @brief Return all the attributes of the node.
     *
     * @return array The attributes
     */
    public function getAttributes() {
        $ao = [];
        foreach($this->attr as $k=>$v) {
            $ao[$k] = $this->getCastValue($v);
        }
        return $ao;
    }

    /**
     * @brief Return a single attribute of the node.
     *
     * This can also be accessed via the properties:
     *
     * @code
     * $attr = $node->getAttribute("foo");
     * // ...is the same as...
     * $attr = $node->foo;
     * @endcode
     *
     * @param string $name The attribute to return
     * @return mixed The attribute value
     */
    public function getAttribute($name) {
        if (array_key_exists($name,$this->attr))
            return $this->getCastValue($this->attr[$name]);
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
            return $this->getCastValue($this->values[(int)$index]);
        return null;
    }
    public function offsetSet($index,$value) {
        if (is_array($value))
            throw new SdlParseException("Invalid value type for set: <array> is not allowed");
        if ($index === null) {
            $this->values[] = $this->getSingleTypedValue($value);
        } else {
            $this->values[(int)$index] = $this->getSingleTypedValue($value);
        }
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
            return $this->getCastValue($this->attr[$key]);
        return null;
    }
    public function __set($key,$value) {
        if (is_array($value))
            throw new SdlParseException("Invalid value type for attribute set: <array> is not allowed");
        $this->attr[$key]=$this->getSingleTypedValue($value);
    }
    public function __unset($key) {
        unset($this->attr[$key]);
    }

}

class SdlParseException extends \Exception { }
