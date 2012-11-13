<?php
// !status; might be renamed
// !stability; unstable

namespace Cherry\Mvc\View;

use Cherry\Mvc\View;
use Cherry\Base\Event;

class Php extends View {

    private
            $view = null,
            $content = null;

    public function render($return=false) {
        $cont = $this->load($this->view);
        if ($return)
            return $cont;
        else
            echo $cont;
    }
    
    public function __construct($view = null,array $options = null) {
        parent::__construct();
        // Constructor
        $this->view = $view;
    }

    function __ob_callback($str) {
        $pos = strpos($str,'<@');
        if ($pos === false) {
            return $str;
        }
        $end = strpos($str,'>',$pos);
        while ($end > $pos) {
            $s_pre = substr($str,0,$pos);
            $s_tag = substr($str,$pos+1,($end-$pos)-1);
            $s_aft = substr($str,$end+1);
            if (substr($s_tag,strlen($s_tag)-1,1) == '/') {
                $s_tag = trim(substr($s_tag,0,strlen($s_tag)-1));
            }
            $parts = explode(' ',$s_tag);
            $tag = $parts[0];
            $attr = array();
            for($n = 1;$n<count($parts);$n++) {
                $attrsep = strpos($parts[$n],'=');
                if ($attrsep===false) {
                    $this->attr[$parts[$n]] = true;
                } else {
                    $aname = substr($parts[$n],0,$attrsep);
                    $aval = trim(substr($parts[$n],$attrsep+1),"\"'");
                    $attr[strtolower($aname)] = $aval;
                }
            }
            if ($tag == '@content') {
                $cont = $this->content;
            } elseif ($tag == '@include') {
                // Switch based on the module
                if (!empty($attr['type'])) {
                    switch(strtolower($attr['type'])) {
                        case 'widget':
                            if (!empty($attr['class'])) {
                                $cn = $attr['class'];
                                $cn = '\\'.str_replace('.','\\',$cn);
                                $widget = new $cn();
                                $cont = $widget->render();
                            } else {
                                $cont = '<div>Error: Widget requires class attribute</div>';
                            }
                            break;
                        default:
                            $cont = '<div>Error: Unknown include type "'.$attr['type'].'"</div>';
                    }
                }
            } else {
                $cont = Event::invoke('cherry:mvc.render.specialtag',$s_tag,$attr);
            }
            $str = $s_pre.$cont.$s_aft;
            $pos = strpos($str,'<@');
            if ($pos === false) {
                return $str;
            }
            $end = strpos($str,'>',$pos);
        }
        return $str;
    }

    private function load($file) {

        if (defined('IS_PROFILING'))
            \App::profiler()->log("Loading and parsing view");
        ob_start();
        ob_start(array($this,'__ob_callback'));
        include $file;
        ob_end_flush();
        $fout = ob_get_contents();
        ob_end_clean();
        
        return $fout;

    }

}
