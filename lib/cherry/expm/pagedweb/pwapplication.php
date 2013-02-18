<?php

namespace Cherry\Expm\PagedWeb;
define("PWAPP_NS","\\Cherry\\Expm\\PagedWeb");
use Cherry\Web\WebApplication;
use Cherry\Web\HtmlTag as h;

/**
 *
 *
 *
 *
 */
class PWApplication extends WebApplication {

    public function onRequest() {

    /*
        $pr = \Cherry\Base\PathResolver::getInstance();
        $router = $this->getRouter();
        //$this->raiseGlobalEvent("cherry:core:application:routercreated", $router);
        $router->addRoute("^/debug[/:type]$", function($request) {

        });
        $router->route();
        */

        $obj = (count($this->request->segments)>0)?$this->request->segments[0]:null;
        switch($obj) {
            //// U
            case 'pwapp':
                if (count($this->request->segments)>2) {
                    $ci = array_slice($this->request->segments,1,count($this->request->segments)-1);
                    $cp = join("\\",array_slice($ci,0,count($ci)-1));
                    $cm = $ci[count($ci)-1];
                } elseif (count($this->request->segments)>1) {
                    $cp = $this->request->segments[1];
                    if (strpos(".",$cp)===false) {
                        $cm = $cp;
                        $cp = 'default';
                    } else {
                        $cm = "index.phtml";
                    }
                } else {
                    $cp = 'default';
                    $cm = 'index.phtml';
                }
                list($cm,$opts) = explode(".",$cm,2);
                echo "pwapp::{$cp}:{$cm}[$opts]";
                break;

            //// Debugging ////////////////////////////////////////////////////
            case 'debug':
                $cmd = (count($this->request->segments)>1)?$this->request->segments[1]:null;
                switch($cmd) {
                    case 'request':
                        var_dump($this->request);
                        break;
                    case 'server':
                        var_dump($_SERVER);
                        break;
                    default:
                        echo h::ul(
                            h::li(h::a("Request variables")->_href("/debug/request"))
                        );
                        break;
                }
                break;

            //// Default
            default:
                $this->redirect("/pwapp/core/index.phtml");
                break;
        }
    }

    public function redirect($url) {
        header("Location: {$url}");
        exit;
    }

}
