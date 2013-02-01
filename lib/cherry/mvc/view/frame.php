<?php

namespace Cherry\Mvc\View;

use Cherry\Base\Event;

class Frame extends Base {

    private $frame = null;
    private $app = null;
    private $content = null;

    public function __construct($frame,array $options = null) {
        // Constructor
        $this->frame = $frame;
        $this->app = \cherry\Lepton::getInstance()->getApplication();
    }

    function __ob_callback($str) {
        $out = '';
        $offs = 0;
        do {
            $pos = strpos($str,'<@');
            if ($pos === false) {
                $out.= $str;
                $str = '';
            }
            $end = strpos($str,'>',$pos);
            $out.= substr($str,0,$pos);
            if ($end > $pos) {
                $s_tag = trim(substr($str,$pos+1,($end-$pos)-1));
                $str = substr($str,$end+1);
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
                        $aval = substr($parts[$n],$attrsep+1);
                        if ($aval[0] == '"') {
                            if ($aval[strlen($aval)-1] == '"') {
                                // Just unquote it
                                $attr[$aname] = substr($aval,1,strlen($aval)-2);
                            } else {
                                $fvals = array(substr($aval,1));
                                do {
                                    $n++;
                                    if (strpos($parts[$n],'"')!==false) {
                                        $fvals[] = $parts[$n];
                                    } else {
                                        $fvals[] = substr($parts[$n],strlen($parts[$n]-1));
                                        break;
                                    }
                                } while(true);
                                $attr[$aname] = join(" ",$fvals);
                            }
                        }
                    }
                }
                switch($tag) {
                    case '@header':
                        $cont = Event::invoke('cherry:mvc.render.head',$tag,(array)$attr);
                        break;
                    case '@embed':
                        $cont = '[EMBED]';
                        break;
                    case '@widget':
                        $cont = '[WIDGET]';
                        break;
                    case '@content':
                        $cont = $this->content;
                        break;
                    default:
                        $cont = Event::invoke('cherry:mvc.render.specialtag',$tag,(array)$attr);
                        break;
                }
                $out.= $cont;
            }
        } while ($str);
        return $out;
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

        printf("%s",$fout);

        /*
        // Specify configuration
        $config = array(
            'doctype' => 'HTML',
            'indent'         => true,
            'output-xhtml'   => false,
            'wrap'           => 120
        );

        // Tidy
        $tidy = new \tidy();
        $tidy->parseString($fout, $config, 'utf8');
        $tidy->cleanRepair();
        printf("%s", $tidy);
        */

        // Load frame
    }

}
