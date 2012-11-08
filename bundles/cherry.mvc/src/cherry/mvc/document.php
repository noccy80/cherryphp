<?php

namespace Cherry\Mvc;

if (!defined('_NL_')) define('_NL_',"\n");

class Document {

    /// HTML 5
    const DT_HTML5 = '<!DOCTYPE html>';
    /// HTML 4.01 Strict:This DTD contains all HTML elements and attributes,
    /// but does NOT INCLUDE presentational or deprecated elements (like font).
    /// Framesets are not allowed.
    const DT_HTML401_STRICT = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
    /// HTML 4.01 Transitional: This DTD contains all HTML elements and
    /// attributes, INCLUDING presentational and deprecated elements (like
    /// font). Framesets are not allowed.
    const DT_HTML401_TRANSITIONAL = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
    /// HTML 4.01 Frameset: This DTD is equal to HTML 4.01 Transitional, but
    /// allows the use of frameset content.
    const DT_HTML401_FRAMESET = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">';
    /// XHTML 1.0 Strict: This DTD contains all HTML elements and attributes,
    /// but does NOT INCLUDE presentational or deprecated elements (like font).
    /// Framesets are not allowed. The markup must also be written as
    /// well-formed XML.
    const DT_XHTML10_STRICT = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
    /// XHTML 1.0 Transitional: This DTD contains all HTML elements and
    /// attributes, INCLUDING presentational and deprecated elements (like
    /// font). Framesets are not allowed. The markup must also be written as
    /// well-formed XML.
    const DT_XHTML10_TRANSITIONAL = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
    /// XHTML 1.0 Frameset: This DTD is equal to XHTML 1.0 Transitional, but
    /// allows the use of frameset content.
    const DT_XHTML10_FRAMESET = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">';
    /// XHTML 1.1: This DTD is equal to XHTML 1.0 Strict, but allows you to
    /// add modules (for example to provide ruby support for East-Asian
    /// languages).
    const DT_XHTML11 = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">';

    private
            $doctype = null,    ///< DocType of the document
            $xhtml = false,     ///< Use XHTML tags for <br/> and the likes
            $headers = [],
            $meta = [],
            $scripts = [],
            $inlinescripts = [],
            $styles = [],
            $title = null,
            $chunked = false,
            $body = '',
            $ob_active = false,
            $lang = null,
            $charset = null;
    private static
            $document = null;

    static function get() {
        return self::$document;
    }

    /**
     *
     */
    static function begin($doctype = Document::DT_HTML5, $lang = null, $charset = null) {
        self::$document = new Document($doctype, $lang, $charset);
        return self::$document;
    }

    public function end() {
        if ($this->ob_active) {
            $this->body .= ob_get_contents();
            ob_end_clean();
            $this->ob_active = false;
        }
    }
    
    public function setCachePolicy($policy) {
        if (in_array($policy,[null,'public','private','private_no_expire','nocache']))
            return session_cache_limiter($policy);
        user_error('Invalid cache policy (cache_limiter) assign: '.$policy);
    }

    /**
     *
     */
    public function __construct($doctype,$lang,$charset) {
        $this->doctype = $doctype;
        $this->lang = $lang;
        if ($charset) $this->setCharset($charset);
        ob_start(array($this,'_ob_handler'));
        $this->ob_active = true;
    }

    public function _ob_handler($content,$flags) {
        $this->body .= $content;
    }

    public function __toString() {
        return $this->getContent();
    }

    private function getDocumentHead() {
        if ($this->lang)
            $doc = '<html lang="'.$this->lang.'">';
        else
            $doc = '<html>';
        $doc.= '<head>';
        $doc.= '<title>'.htmlentities($this->title).'</title>';
        foreach($this->meta as $key=>$meta) {
            list($type,$value) = $meta;
            if ($type == 'meta') {
                $doc.= sprintf('<meta name="%s" content="%s">', $key, htmlentities($value));
            } elseif ($type == 'http-equiv') {
                $doc.= sprintf('<meta http-equiv="%s" content="%s">', $key, htmlentities($value));
            } elseif ($type == 'charset') {
                $doc.= sprintf('<meta charset="%s">', $value);
            }
        }
        $inlinestyle = '';
        foreach($this->styles as $style) {
            list($type,$file) = $style;
            if ($type == 'link')
                $doc.= sprintf('<link rel="stylesheet" href="%s">', $file);
            else
                $inlinestyle.= $file;
        }
        if ($inlinestyle) $doc.= '<style type="text/css">'.$inlinestyle.'</style>';
        // HTML5 doesn't use the type attribute.
        foreach($this->scripts as $script) {
            list($file,$type) = $script;
            if ($this->doctype == self::DT_HTML5)
                $doc.= sprintf('<script src="%s"></script>', $file);
            else
                $doc.= sprintf('<script src="%s" type="%s"></script>', $file, $type);
        }
        foreach($this->inlinescripts as $k=>$v) {
            $doc.= sprintf('<script type="%s">%s</script>', $k, $v);
        }
        $doc.= '</head>';
        $doc.= '<body>';
        return $doc;
    }

    private function getDocumentFoot() {
        $doc = '</body></html>';
        return $doc;
    }

    public function addScript($file,$type='text/javascript') {
        $this->scripts[] = [ $file, $type ];
    }

    public function addInlineScript($string, $type='text/javascript') {
        if (!array_key_exists($type,$this->inlinescripts)) {
            $this->inlinescripts[$type] = '';
        }
        $this->inlinescripts[$type].= $string."\n";
    }

    public function addStyleSheet($file) {
        $this->styles[] = [ 'link', $file ];
    }
    public function addInlineStyleSheet($string) {
        $this->styles[] = [ 'inline', $string ];
    }

    public function setMeta($key,$value) {
        $this->meta[$key] = [ 'meta', $value ];
    }
    public function setHttpEquiv($key,$value) {
        $this->meta[$key] = [ 'http-equiv', $value ];
    }
    public function setCharset($charset) {
        $charset = strtoupper($charset);
        if ($this->doctype == self::DT_HTML5) {
            $this->meta['charset'] = [ 'charset', $charset ];
        } else {
            $this->setHttpEquiv('content-type','text/html; charset='.$charset);
        }
        $this->charset = $charset;
    }

    public function getContent() {
        $this->end();
        $out =  $this->doctype._NL_.
                $this->getDocumentHead()._NL_.
                $this->body._NL_.
                $this->getDocumentFoot();
        if (function_exists('tidy_parse_string')) {
            $config = array('indent' => true,
                            'output-xhtml' => $this->xhtml,
                            'input-encoding' => $this->charset,
                            'output-encoding' => $this->charset,
                            'drop-empty-paras' => false,
                            'language' => $this->lang,
                            'vertical-space' => false,
                            'wrap' => 200);
            $tidy = tidy_repair_string($out, $config, strtolower(str_replace(' ','',$this->charset)));
            $out = (string)$tidy._NL_;
            $out = str_replace(">\n</script>","></script>",$out);
        }
        return  $out;
    }

    public function __destruct() {
        $this->end();
    }

    /**
     *
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    public function getTitle() {
        return $this->title;
    }

}
