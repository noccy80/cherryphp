<?php
// !status; might be renamed
// !stability; unstable

namespace Cherry\Mvc\View;

use Cherry\Mvc\View;

class Php extends View {

    private $frame = null;
    private $app = null;
    private $content = null;

    public function __construct($frame,array $options = null) {
        // Constructor
        $this->frame = $frame;
        $this->app = \cherry\Lepton::getInstance()->getApplication();
    }

    function __ob_callback($str) {
        $pos = strpos($str,'<@');
        if ($pos === false) {
            return $str;
        }
        $end = strpos($str,'>',$pos);
        if ($end > $pos) {
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
                    $aname = substr($parts[$n],0,$attrsep-1);
                    $aval = substr($parts[$n],$attrsep);
                    $attr[$aname] = $aval;
                }
            }
            if ($s_tag == '@content') {
                $cont = $this->content;
            } else {
                $cont = Event::invoke('cherry:mvc.render.specialtag',$s_tag,$attr);
            }
            return $s_pre.$cont.$s_aft;
        }
        return $str;
    }

    function load($view) {

        $paths = $this->app->getConfiguration('application','paths');

        $base = CHERRY_APP . '/application/views/scripts/';
        $path = join(DIRECTORY_SEPARATOR,array($base,$view));
        ob_start();
        ob_start(array($this,'__ob_callback'));
        include $path;
        ob_end_flush();
        $this->content = ob_get_contents();
        ob_end_clean();

        $base = CHERRY_APP . '/application/views/';
        $path = join(DIRECTORY_SEPARATOR,array($base,$this->frame));
        ob_start();
        ob_start(array($this,'__ob_callback'));
        include $path;
        ob_end_flush();
        $fout = ob_get_contents();
        ob_end_clean();

        printf("%s", $fout);

        // Load frame
    }

}
