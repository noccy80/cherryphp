<?php

namespace Cherry\Data\Ddl;

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
class SdlTag implements \ArrayAccess, \IteratorAggregate, \Countable {

    private $name       = null;
    private $values     = [];
    private $attr       = [];
    private $children   = [];
    private $comment    = null;
    private $doccomment = null;
    private $ns         = null;
    private $parent     = null;

    /** Strict parsing according to SDL 1.2 */
    const OPT_STRICT = 0x01;

    const PARSER_TAGNAME = 0;
    const PARSER_TAGVALUE = 1;
    const PARSER_TAGATTR = 2;

    /**
     * @brief Create a new SDL node
     *
     * @param string $name The node name (with optional prefixed namespace followed by :, eg. foo:bar)
     * @param array|string $values The value(s) of the node. Can be null.
     * @param array $attr The attributes to attach to the node.
     */
    public function __construct($name = null, $values = null, array $attr = null) {
        if (!$name) $name = null;
        if (strpos($name,':')!==false) {
            list($this->ns,$this->name) = explode(':',$name,2);
        } else {
            $this->name = $name;
        }
        // Extract the values as typed values
        foreach((array)$values as $val) {
            if (!($val instanceof SdlTypedValue))
                $val = SdlTypedValue::parse($val);
            $this->values[] = $val;
        }
        foreach((array)$attr as $k=>$value) {
            if (!($value instanceof SdlTypedValue))
                $value = SdlTypedValue::parse($value);
            $this->attr[$k] = $value;
        }
    }

    public static function createFromString($string) {
        $tag = new SdlTag("root");
        $tag->loadString($string);
        return $tag;
    }

    public static function createFromFile($filename) {
        $string = file_get_contents($filename);
        return self::createFromString($string);
    }

    public function loadFile($filename) {
        $string = file_get_contents($filename);
        $this->loadString($string);
    }

    /**
     * Parse a string recursively
     *
     *
     */
    public function loadString($str,$opts=null) {

        static $level = 0;

        if (!is_array($str)) {
            $toks = token_get_all("<?php ".$str);
            // Get rid of the opening tag
            array_shift($toks);
            $level = 0;
        } else {
            $toks = $str;
            $level++;
        }

        // Helpers

        $pstate = self::PARSER_TAGNAME; // parser state, what we are expecting
        $buf = null; // Holding the current buffer
        $lasttok = null; // Holding the last token for attr assignment
        $tagname = null; // The parsed tag name
        $tagvals = []; // The tag values
        $tagattr = []; // The tag attributes
        $break = false; // flag to indicate end of tag

        while(count($toks)>0) {
            $thistok = array_shift($toks);

            // Get the string representation of the token
            if (is_array($thistok)) { $thisstr = $thistok[1]; }
            else { $thisstr = $thistok; }
            // we do this to only detect newlines, we don't care about the
            // padding around it.
            if (strpos($thisstr,"\n")!==false) $thisstr = "\n";

            // Parse the tokens
            $break = false;
            //echo "\033[1m{$level}\033[0m\033[7m{$thisstr}\033[0m\n";
            switch($thisstr) {
                case "}":
                    if ($level <= 0)
                        throw new SdlParserException("Recursion level mismatch on '}'", SdlParserException::ERR_RECURSION_MISMATCH);
                    //echo "Leaving child...\n";
                    $pstate = self::PARSER_TAGNAME;
                    $level--;
                    return $toks;
                    //echo "Ascend: {$buf}\n";
                    $buf = null;
                    // ascend
                    break;
                case ";":
                case "\n":
                case "\r":
                case "{":
                    $break = true;

                case " ":
                    if (!$break) {
                        // is this part of a date?
                        $next = $toks[0]; if (is_array($next)) $next = $next[1];
                        $next2 = $toks[1]; if (is_array($next2)) $next2 = $next2[1];
                        $next.= $next2;
                        if ((preg_match("/^[0-9]{4}\/[0-9]{2}\/[0-9]{2}$/", $buf)
                         && (preg_match("/^[0-9]{2}:/", $next)))) {
                            $buf.=" ";
                            break;
                        }
                    }
                    if ($buf) {
                        if ($pstate == self::PARSER_TAGATTR) {
                            // Found a tag attribute
                            $lt = SdlTypedValue::parse($buf);
                            if (!$lt)
                                throw new SdlParserException("Unparsed attribute value: {$buf}");
                            $tagattr[$lasttok] = $lt;
                            //echo "  Attr: {$lasttok} = {$buf}\n";
                            $pstate = self::PARSER_TAGVALUE;
                        } elseif ($pstate == self::PARSER_TAGVALUE) {
                            // Found a tag value
                            $tv = SdlTypedValue::parse($buf);
                            if (!$tv)
                                throw new SdlParserException("Unparsed value: {$buf}");
                            $tagvals[] = $tv;
                            //echo "  Value: {$buf} parsed as {$tv}\n";
                        } elseif ($pstate == self::PARSER_TAGNAME) {
                            // Found a tag name, inspect and see if it is a valid
                            // tag name, and if not create an anonymous tag.
                            if ($this->parserIsValidIdentifier($buf)) {
                                $tagname = $buf;
                                //echo "Tag: {$buf}\n";
                            } else {
                                $tagname = null;
                                $tagvals[] = SdlTypedValue::parse($buf);
                                //echo "(anon)\n  Value: {$buf}\n";
                            }
                            $pstate = self::PARSER_TAGVALUE;
                        }
                    }
                    if ($thisstr == "{") {
                        //echo "Entering child...\n";
                        if (!empty($tagname) || !empty($tagvals)) {
                            $tag = new SdlTag($tagname,$tagvals,$tagattr);
                            $toks = $tag->loadString($toks,$opts);
                        }
                        $break = true;
                    } elseif ($break) {
                        if (!empty($tagname) || !empty($tagvals)) {
                            $tag = new SdlTag($tagname,$tagvals,$tagattr);
                        }
                    }
                    // If we are at the end of the tag, reset the state
                    if ($break) {
                        if (!empty($tag))
                            $this->children[] = $tag;
                        $tag = null;
                        $tagname = null;
                        $tagvals = []; $tagattr = [];
                        $pstate = self::PARSER_TAGNAME;
                        $break = false;
                    }
                    $buf = null;
                    // new state
                    break;
                case "=":
                    // Remember the last token and set the parser state to
                    // expect an attribute value.
                    if ($this->parserIsValidIdentifier($buf)) {
                        $lasttok = $buf;
                        $pstate = self::PARSER_TAGATTR;
                    } else {
                        throw new SdlParserException("Invalid identifier '{$buf}' used as attribute near line {$lline}", SdlParserException::ERR_INVALID_IDENTIFIER);
                    }
                    $buf = null;
                    // attribute value asign
                    break;
                default:
                    //echo "Pushing to buffer: {$thisstr}\n";
                    $buf.= $thisstr;

            }
        }

    }

    /**
     * Check if the name is a valid identifier according to SDL 1.2
     *
     */
    private function parserIsValidIdentifier($name) {
        // From the SDL language guide: An SDL identifier starts with a unicode
        // letter or underscore (_) followed by zero or more unicode letters,
        // numbers, underscores (_), dashes (-), periods (.) and dollar signs
        // ($).
        return (preg_match("/^[_a-zA-Z]{1}[_\-\.\$a-zA-Z0-9]*/", $name));

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
                $node.=" ".$value->encode();
            }
        }
        if (count($this->attr)>0) {
            foreach($this->attr as $k=>$v) {
                $v = $v->encode();
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

    public function encodeChildren() {
        $nodes = null;
        foreach($this->children as $child)
            $nodes .= $child->encode();
        return $nodes;
    }



    //// BASE GETTERS AND SETTERS //////////////////////////////////////////////

        /**
     * @brief Return the name of the node.
     *
     * @return string The node name
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @brief Return the name with the namespace prepended.
     *
     * @return string The name with the namespace prepended.
     */
    public function getNameNs() {
        if (!empty($this->ns))
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
     * @brief Set the node doc comment.
     *
     * You can set the comment to null to remove it.
     *
     * @param string $str The doc comment
     */
    public function setDocComment($str) {
        if (substr(ltrim($str),0,3) == "/**") {
            $cout = [];
            $cmt = trim($str,"/*\n");
            $cmtl = explode("\n",$cmt);
            foreach($cmtl as $cmtr) $cout[] = ltrim($cmtr,"* ");
            $str = trim(join("\n",$cout));
        }
        $this->doccomment = $str;
    }

    /**
     * @brief Get the node doccomment
     *
     * @return string The doccomment (or null)
     */
    public function getDocComment() {
        return $this->doccomment;
    }



    //// PARENT CONTROL ////////////////////////////////////////////////////////

    public function getParent() {
        return $this->parent;
    }

    public function setParent($parent) {
        $this->parent = $parent;
    }



    //// CHILDREN AND ENUMERATION //////////////////////////////////////////////

    /**
     * Get an iterator for the children
     */
    public function getIterator() {
        return new \ArrayIterator($this->children);
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
     * @brief Check if a node has child nodes
     *
     * @return bool True if the node has child nodes
     */
    public function hasChildren() {
        return (count($this->children)>0);
    }

    /**
     * @brief Return the first child whose node ame match the string.
     *
     * @param string $name The node name to match
     * @param string $withvalue The node value to match (or null)
     * @return SdlTag The first matching node or null
     */
    public function getChild($name,$withvalue=null) {
        if (is_integer($name)) {
            if (!empty($this->children[$name]))
                return $this->children[$name];
            return null;
        }
        foreach($this->children as $nod) {
            if ($nod->getName() == $name) {
                if (!$withvalue) return $nod;
                if ($withvalue == $nod->getValue()) return $nod;
            }
        }
        return null;
    }

    /**
     * @brief Add a child node to the node.
     *
     * @param SdlTag $node The node to append
     */
    public function addChild(SdlTag $node) {
        $node->setParent($this);
        $this->children[] = $node;
    }

    /**
     * @brief Remove a child; node must match exact (===)
     *
     * @param SdlTag $node The node to delete.
     */
    public function removeChild(SdlTag $node) {
        $this->children = array_filter(
            $this->children,
            function($nv) use ($node) {
                return (!($nv === $node));
            }
        );
    }



    //// VALUE ACCESS VIA ARRAYACCESS //////////////////////////////////////////

    /**
     * Return the number of values
     */
    public function count() {
        return count($this->values);
    }

    /**
     * Get a value
     */
    public function offsetGet($index) {
        if (isset($this->values[(int)$index])) {
            return $this->values[(int)$index]->getValue();
        }
        return null;
    }

    /**
     * Set a value
     */
    public function offsetSet($index,$value) {
        if (is_array($value))
            throw new SdlParseException("Invalid value type for set: <array> is not allowed");
        if ($index === null) {
            $this->addValue($value);
        } else {
            $this->setValue($value,$index);
        }
    }

    /**
     * Unset a value
     */
    public function offsetUnset($index) {
        if (isset($this->values[(int)$index]))
            unset($this->values[(int)$index]);
    }

    /**
     * Check if a value is set
     */
    public function offsetExists($index) {
        return (isset($this->values[(int)$index]));
    }

    /**
     * @brief Return all the values of the node
     *
     * @return array The values
     */
    public function getValues() {
        $vo = [];
        foreach($this->values as $vl) $vo[] = $vl->getValue();
        return $vo;
        //return $this->values;
    }

    /**
     * @brief Set the value at a index.
     *
     * @param Mixed $value The value to set
     * @param int $index The index (default 0)
     * @param int $type The type to assign (null=detect)
     */
    public function setValue($value,$index=0,$type=null) {
        if (!($value instanceof SdlTypedValue))
            $value = SdlTypedValue::parse($value);
        $this->values[$index] = $value;
    }

    public function setBinaryValue($value,$index=0) {
        $value = new SdlTypedValue($value, SdlTypedValue::LT_BINARY);
        $this->values[$index] = $value;
    }

    /**
     * @brief Add the value to an attribute.
     *
     * This function will not overwrite anything.
     *
     * @param Mixed $value The value to set
     */
    public function addValue($value) {
        if (!($value instanceof SdlTypedValue))
            $value = SdlTypedValue::parse($value);
        $this->values[] = $value;
    }

    /**
     * @brief Return a value from the node.
     *
     * @param int $index The index to retrieve.
     * @return mixed The first value of the node
     */
    public function getValue($index=0) {
        return $this->values[$index]->getValue();
    }

    /**
     * Get value as from a matrix [row][col]
     *
     */
    public function getValueMatrix($row,$column) {

    }

    /**
     * Return direct child values as a value map, discarding attributes.
     *
     * $map = [
     *     'foo' => 'Bar',
     *     'bar' => 'Baz'
     * ];
     *
     */
    public function getValueMap() {

    }



    //// ATTRIBUTE ACCESS VIA PROPERTIES ///////////////////////////////////////

    /**
     * Get an attribute
     */
    public function __get($key) {
        return $this->getAttribute($key);
    }

    /**
     * Set an attribute
     */
    public function __set($key,$value) {
        if (is_array($value))
            throw new SdlParseException("Invalid value type for attribute set: <array> is not allowed");
        if (array_key_exists($key,$this->attr)) {
            $this->attr[$key]->setValue($value);
        } else {
            $this->attr[$key] = SdlTypedValue::parse($value);
        }
    }

    /**
     * Unset an attribute
     */
    public function __unset($key) {
        $this->removeAttribute($key);
    }

    /**
     * Check if attribute is set
     */
    public function __isset($key) {
        return $this->hasAttribute($key);
    }


    /**
     * @brief Return all the attributes of the node.
     *
     * @return array The attributes
     */
    public function getAttributes() {
        $out = [];
        foreach($this->attr as $k=>$v) {
            $out[$k] = $v->getValue();
        }
        return $out;
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
            return $this->attr[$name]->getValue();
        return null;
    }

    /**
     *
     */
    public function hasAttribute($name) {
        return array_key_exists($name,$this->attr);
    }

    /**
     *
     *
     */
    public function setAttribute($name,$value) {
        if (!($value instanceof SdlTypedValue))
            $value = SdlTypedValue::parse($value);
        $this->attr[$name] = $value;
    }

    /**
     *
     */
    public function removeAttribute($name) {
        $this->attr[$name] = null;
        unset($this->attr[$name]);
    }


    //// XPATH-LIKE QUERIES ON TAGS ////////////////////////////////////////////

    /**
     * Perform a query on the node, or if the expression starts with / from the
     * root node.
     *
     */
    public function query($expr) {
        // \debug("Evaluating spath: {$expr}");
        if ($expr == "") {
            return $this;
        } elseif ($expr[0] == "/") {
            // Find the root of the document and pass the query on
            $root = $this;
            while(($newroot = $root->getParent())) $root = $newroot;
            return $root->query(substr($expr,1));
        } else {
            // Grab the first part of the expression
            // TODO: Make this a regex, until then any / will break the expression
            list($parse,$expr) = explode("/",$expr.'/',2);
            $expr = rtrim($expr,"/");
            if (strpos($parse,"[")!==false) {
                $match = explode("[",$parse);
                $tagname = array_shift($match);
                foreach($match as $k=>$v) $match[$k] = rtrim($v,"]");
            } else {
                $tagname = $parse;
                $match = [];
            }
            // Enumerate the children for matching tagnames
            $ret = [];
            foreach($this->children as $node) {
                $matched = true;
                if (fnmatch($tagname,$node->getName())) {
                    if (count($match)>0) {
                        foreach($match as $m) {
                            if ($m[0] == '@') {
                                $m = substr($m,1);
                                if (strpos($m,"=")===false) {
                                    $attr = $m;
                                    if (!$node->hasAttribute($attr)) $matched = false;
                                } else {
                                    list($attr,$val) = explode("=",$m,2);
                                    if ($val == "true") $val = true;
                                    elseif ($val == "false") $val = false;
                                    elseif ($val == "null") $val = null;
                                    if ($node->{$attr} !== $val) $matched = false;
                                }
                                // Test attribute
                            } else {
                                if (!in_array($m,$node->getValues())) {
                                    $matched = false;
                                    break;
                                }
                            }
                        }
                    }
                    if ($matched) {
                        $cn = $node->query($expr);
                        if (is_array($cn))
                            $ret = array_merge($ret,$cn);
                        elseif ($cn)
                            $ret[] = $cn;
                    }
                }
            }
            return $ret;
        }
    }


}














class OldSdlTag {

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
    // States for parser
    const   SP_NODENAME  = 0; // Expecting node name
    const   SP_NODEVALUE = 1; // Expecting node value
    const   SP_VALUELIST = 2; // We are in a value list
    const   SP_ATTRIBUTE = 3; // We are assigning to an attribute
    const   SP_NODEB64C = 4; // Base64-chunk for node value.

    private $name       = null;
    private $values     = null;
    private $attr       = [];
    private $children   = [];
    private $comment    = null;
    private $doccomment = null;
    private $ns         = null;
    private $parent     = null;

    /**
     * @brief Load a file as children to the current node.
     *
     * @param string $file The filename to load
     */
    public function loadFile($file) {
        // TODO: Check for errors
        $fc = file_get_contents($file);
        $this->decode($fc);
    }

    /**
     * @brief Load a string as children to the current node.
     *
     * @param string $str The string containing SDL data to decode
     */
    public function loadString($str) {
        $this->decode($str);
    }

    public static function createFromFile($file) {
        $tag = new SdlTag("root");
        $tag->loadFile($file);
        return $tag;
    }

    /**
     * @brief DEPRECATED: Decode a string into the node.
     * @see loadString()
     *
     *
     */
    public function decode($string,array $options=null) {
        $opts = (object)array_merge([ 'verify_base64'=>true ], (array)$options);
        if (!is_array($string)) {
            $subnodes = [];
            $depth = 0;
            // Opening tag required for the parser to do it's thing.
            $toks = token_get_all("<?php {$string}\n");
        } else {
            $toks = $string;
        }


        // Local state for parser
        $_attrn = null;
        $_b64data = null;
        $_attr = [];
        $_name = null;
        $_vals = [];
        $_doccomment = null;
        $_comment = null;
        $_final = false;
        $_recurse = false;
        $_ret = false;
        $_ns = null;
        $_wspace = null;
        $idx = 0;
        $state = self::SP_NODENAME;

        // Go over all the tokens
        while (count($toks)>0) {
            $tok = array_shift($toks);
            if (is_array($tok)) {
                $str = $tok[1];
                $line = $tok[2];
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
                    case T_IS_EQUAL:
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
                            $_attrn = $this->toAttributeName($_attrn);
                            if ($this->getTypedValue($str,$tok[0],$value)) {
                                $_attr[$_attrn] = $value;
                            } else {
                                $_attr[$_attrn] = $str;
                            }
                            $state = self::SP_NODEVALUE;
                        } elseif ($state == self::SP_NODEB64C) {
                            $_b64data.=$str;
                        } else {
                            throw new SdlParseException("Value token without state on line {$line}.");
                        }
                        break;

                    // Strings and numbers
                    case T_CONSTANT_ENCAPSED_STRING:
                        if ((substr($str,0,1) == "\"") && (substr($str,-1,1) == "\"")) {
                            $str = substr($str,1,strlen($str)-2);
                        }
                        $str = \stripcslashes($str);
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
                            // Continue if we are parsing base64 data
                            if ($state == self::SP_NODEB64C) break;
                            $_final = true;
                            $state = self::SP_NODENAME;
                            $idx = 0;
                        } else {
                            $_wspace = $str;
                        }
                        break;
                    case T_COMMENT:
                        $str = trim(substr($str,3));
                        if ($_comment) $_comment.="\n".$str;
                        else $_comment = $str;
                        break;

                    case T_DOC_COMMENT:
                        if ($_doccomment) $_doccomment.="\n".$str;
                        else $_doccomment = $str;
                        $str = null;
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
                    case "[":
                        // Begin base64 chunk
                        if ($state == self::SP_NODEVALUE) {
                            $state = self::SP_NODEB64C;
                            $_b64data = null;
                        } else {
                            throw new SdlParseException("Unexpected '[' in data on line {$line}");
                        }
                        break;
                    case "]";
                        // End base64 chunk
                        if ($state == self::SP_NODEB64C) {
                            $state = self::SP_NODEVALUE;
                            $nvalue = null;
                            $str = base64_decode($str);
                            if ($this->getTypedValue($str,'base64',$nvalue)) {
                                $_vals[] = $nvalue;
                                $idx++;
                            }
                        } else {
                            throw new SdlParseException("Unexpected ']' in data on line {$line}");
                        }
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
                    case "-":
                    case ".":
                    case "_":
                        $_wspace = $tok;
                        break;
                    default:
                        throw new SdlParseException("Unhandled string in sdl: {$tok} on line {$line}");
                }
            }
            // The final flag creates the node.
            if ($_final) {
                if ($_name || count($_vals)>0) {
                    if ($_ns) $_name = $_ns.':'.$_name; // Add namespace
                    if (!$_name) $_name = null;
                    $cnod = new SdlTag($_name,$_vals,$_attr,null,$_comment);
                    if ($_doccomment) $cnod->setDocComment($_doccomment);
                    $_comment = null;
                    $_doccomment = null;
                    if ($_recurse) $toks = $cnod->decode($toks);
                    $this->children[] = $cnod;
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

    private function toAttributeName($value) {
        if ($value === null) return "null";
        if ($value === false) return "false";
        if ($value === true) return "true";
        return $value;
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
                case self::LT_BINARY:
                    $sd = \wordwrap(\base64_encode($val),74);
                    return '['.$sd.']';
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
        } elseif (is_null($str) || ($str=="@NULL")) {
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
     * @param Mixed $tok The token identifier (or keyword, such as "base64")
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
        if ($tok == 'base64') {
            $typedval = [ $value, self::LT_BINARY ];
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
    private function getSingleTypedValue($value,$type=null) {
        $ret = null;
        switch($type) {
            case self::LT_BINARY:
                $type = 'base64';
                break;
            case self::LT_BOOLEAN:
                $type = \T_STRING;
                break;
            default:
                $type = null;
                break;
        }
        if ($this->getTypedValue($value,$type,$ret)) {
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
                case self::LT_BINARY:
                    return $val;
                default:
                    \debug("Warning: Casting from unhandled internal value type.");
                    return (string)$val;
            }
        }
        return $value;
    }



}

class SdlParseException extends \Exception { }
