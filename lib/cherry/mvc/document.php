<?php

namespace Cherry\Mvc;

use App;

if (!defined('_NL_')) define('_NL_',"\n");

/**
 * @brief Combines and renders views.
 *
 * The document is to be assigned a view to render. The document can also contain
 * a decorator, which is basically a view that contains a default <@content />
 * tag for where the actual document view is to be inserted.
 *
 * Provide an appropriate doctype to the constructor, as this will affect how the
 * output is formatted.
 */
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
            $headers = [],      ///<
            $meta = [],         ///< Meta headers
            $scripts = [],      ///< Linked scripts
            $inlinescripts = [],///< Inline scripts
            $styles = [],       ///< Styles
            $title = null,      ///< Document title
            $chunked = false,   ///<
            $body = '',         ///<
            $ob_active = false, ///< Output buffer active (TBD)
            $lang = null,       ///< Language of the document
            $decorator = null,  ///< The decorator if any
            $view = null,       ///< The document view
            $charset = null;    ///< Character set for the document

    private static
            $document = null;

    /**
     * @static
     * @brief Retrieve the current document.
     *
     * @return Document
     */
    static function get() {
        return self::$document;
    }

    /**
     * @static
     * @brief Begin a new document, discarding the old document in the process if
     * any.
     *
     * @return Document
     */
    static function begin($doctype = Document::DT_HTML5, $lang = null, $charset = null) {
        self::$document = new Document($doctype, $lang, $charset);
        App::extend('document',self::$document);
        return self::$document;
    }

    public function end() {
        if ($this->ob_active) {
            $this->body .= ob_get_contents();
            if (ob_get_level()>0) ob_end_clean();
            $this->ob_active = false;
        }
    }

    /**
     * @brief Assign the decorator view.
     *
     *
     */
    public function setDecorator(View $view) {
        $this->decorator = $view;
    }

    /**
     * @brief Retrieve the decorator view.
     *
     */
    public function getDecorator() {
        return $this->decorator;
    }

    public function getView() {
        return $this->view;
    }

    /**
     * @brief Set the document title.
     *
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * @brief Get the document title.
     *
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @brief Set the caching policy for the document.
     *
     */
    public function setCachePolicy($policy) {
        if (in_array($policy,[null,'public','private','private_no_expire','nocache']))
            return session_cache_limiter($policy);
        user_error('Invalid cache policy (cache_limiter) assign: '.$policy);
    }

    /**
     * @brief Add a script url to the document.
     *
     */
    public function addScript($file,$type='text/javascript') {
        $this->scripts[] = [ $file, $type ];
    }

    /**
     * @brief Add an inline script to the document.
     *
     */
    public function addInlineScript($string, $type='text/javascript', $id = null) {
        if (!array_key_exists($type,$this->inlinescripts)) {
            $this->inlinescripts[$type] = [];
        }
        if ($id) {
            $this->inlinescripts[$type][$id] = $string."\n";
        } else {
            $this->inlinescripts[$type][] = $string."\n";
        }
    }

    public function addStyleSheet($file) {
        $this->styles[] = [ 'link', $file ];
    }
    public function addInlineStyleSheet($string) {
        $this->styles[] = [ 'inline', $string ];
    }

    /**
     * @brief Set a meta header value.
     *
     */
    public function setMeta($key,$value) {
        $this->meta[$key] = [ 'meta', $value ];
    }

    /**
     * @brief Set a meta http-equiv header value.
     *
     */
    public function setHttpEquiv($key,$value) {
        $this->meta[$key] = [ 'http-equiv', $value ];
    }

    /**
     * @brief Set the documents character encoding
     *
     * @todo This should apply the enoding to data in the output buffer.
     */
    public function setCharset($charset) {
        $charset = strtoupper($charset);
        if ($this->doctype == self::DT_HTML5) {
            $this->meta['charset'] = [ 'charset', $charset ];
        } else {
            $this->setHttpEquiv('content-type','text/html; charset='.$charset);
        }
        $this->charset = $charset;
    }

    /**
     * @brief Get the document, render any views, and optionally tidy up.
     *
     */
    public function getContent() {
        $this->end();
        if ($this->view) {
            $this->view->setDocument($this);
            if ($this->decorator) {
                $this->decorator->setDocument($this);
                $this->decorator->setContentView($this->view);
                $this->body = $this->decorator->render(true);
            } else {
                $this->body = $this->view->render(true);
            }
            unset($this->view);
        }
        $out =  $this->doctype._NL_.
                $this->getDocumentHead().
                (string)$this->body.
                $this->getDocumentFoot();
        $tidy = (bool)App::config()->get('html.document.tidy', false);
        if (function_exists('tidy_parse_string') && $tidy) {
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
        return $out;
    }

    public function __get($key) {
        switch(strtolower($key)) {
            case 'view':
                return $this->view;
            default:
                user_error("No such property: ".__CLASS__.'->'.$key);
        }
    }

    public function __set($key,$value) {
        switch(strtolower($key)) {
            case 'view':
                $this->view = $value;
                break;
            default:
                user_error("No such property: ".__CLASS__.'->'.$key);
        }
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
            $doc.= sprintf('<script type="%s">%s</script>', $k, join(_NL_,$v));
        }
        $doc.= '</head>';
        $doc.= '<body>';
        return $doc;
    }

    private function getDocumentFoot() {
        $doc = '</body></html>';
        return $doc;
    }

    public function __destruct() {
        $this->end();
    }

}
