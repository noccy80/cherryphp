<?php

namespace Cherry\Core\Rpc;

use \Cherry\Data\Ddl\DocComment;

abstract class RpcObject {
    
    public function getDefinition() {
        $rc = new \ReflectionClass($this);
        $ml = $rc->getMethods();
        $def = [];
        foreach($ml as $mt) {
            $cmt = new DocComment($mt->getDocComment());
            if ($cmt->rpc_visibility) {
                $val = strtolower($cmt->rpc_visibility);
                if ($val == "visible") {
                    $visible = true;
                } 
            } else {
                $visible = false;
            }
            if ($visible) {
                $mtparms = [];
                $mtinfo = $cmt->rpc_info;
                foreach($cmt->param as $p) {
                    $ps = explode(" ",$p,3);
                    $mtparms[] = [
                        "name" => substr($ps[1],1),
                        "direction" => "in",
                        "type" => $ps[0],
                        "info" => $ps[2]
                    ];
                }
                $def[] = [
                    "name" => $mt->getName(),
                    "info" => $mtinfo,
                    "parms"=> $mtparms
                ];
            }
        }
        return $def;
    }
    
    public function getProxy($target) {
        $rpo = new RpcProxy();
        $rpo->setup($this->getDefinition());
        $rpo->connect($target);
        return $rpo;
    }
    
}
