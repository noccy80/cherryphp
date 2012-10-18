<?php

namespace Cherry\Cwt\Layouts;

use Cherry\Cwt\Widgets\Widget;

class VerticalStack extends Stack {

    private $curlayout = array();
    private $resized = true;

    public function __construct(array $layout=null) {

        $this->initprops(array(
            'layout' => 'string null',
            'keys' => 'string',
            'fill' => 'boolean'
        ),array(
            'layout' => null,
            'fill' => false,
            'keys' => '',
        ));

        if ($layout) {
            $this->fill = true;
            $this->layout = join(',',array_values($layout));
            $this->keys = join(',',array_keys($layout));
            foreach(array_keys($layout) as $key)
                $this->registerprop($key,null);
        } else {

        }

    }

    public function draw() {
        static $drawqueue = array();
        if ($this->resized) {
            \Cherry\debug("Viewport has been resized, recalculating layout...");
            $alloc = 0;
            $numfill = 0;
            $layout = explode(',',$this->layout);
            for ($n = 0; $n < count($layout); $n++) {
                if ($layout[$n] == -1) {
                    $numfill++;
                } else {
                    $alloc+= $layout[$n];
                }
            }
            if ($numfill>0) {
                $sizefill = ($this->height - $alloc) / $numfill;
            }
            $keys = explode(',',$this->keys);
            $drawqueue = array();
            for ($n = 0; $n < count($layout); $n++) {
                if ($layout[$n] == -1) {
                    $drawqueue[$keys[$n]] = $sizefill;
                } else {
                    $drawqueue[$keys[$n]] = $layout[$n];
                }
            }
        }
        $row = 0;
        foreach ($drawqueue as $key=>$height) {
            $this->curlayout[$key] = $height;
            $ctl = $this->{$key};
            if ($ctl) {
                if ($this->resized) {
                    $ctl->moveTo(0,$row,$this->width,$height);
                }
                $ctl->draw();
            }
            $row+= $height;
        }
        $this->resized = false;
    }

    public function hitTest($x,$y) {
        ncurses_mvaddstr(11,5,sprintf("Hittesting %dx%d",$x,$y));
        $offs = 0;
        foreach($this->curlayout as $key=>$height) {
            if ($y < $height + $offs) {
                ncurses_mvaddstr(12,5,sprintf("Matched key %s",$key));
                $obj = $this->{$key};
                if (!empty($obj) && $obj instanceof Widget) {
                    return $obj->hitTest($x,$y);
                }
            } else {
                $offs += $height;
            }
        }
    }

}
