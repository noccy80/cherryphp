<?php

namespace Cherry\Expm\Cwt\Widget;

use \Cherry\Types\Rect;
use \Cherry\Expm\Components as c;

/**
 *
 *
 *
 * Widgets can react to a number of events, including:
 *  * onCreate/onDestroy - when widget is created/destroyed
 *  * onHittest - when a hittest occurs, recurse until the control is found.
 */
abstract class Widget {

    const ALIGN_LEFT = 0;
    const ALIGN_CENTER = 1;
    const ALIGN_RIGHT = 2;

    const COLOR_DEFAULT = 0;
    const COLOR_DIALOGBG = 1;
    const COLOR_DIALOGTEXT = 2;

    protected $rect;

    public function __construct($id = null, Rect $rect = null) {
        // Assign a rect, any rect.
        $this->rect = $rect?:(new Rect());
        \debug("Created widget");
        $this->onCreate();
    }

    public function __destruct() {
        $this->onDestroy();
    }

    /// @var The child widgets of this widget,
    protected $children = [];
    protected $parent = null;
    protected $window;
    /// @var All widgets default to visible.
    protected $visible = true;
    /// @var Boolean determining if the widget can receive focus
    protected $can_focus = true;
    // @var Alignment
    protected $align;

    final private function postMessage($type,Widget $dest,array $data) {
        $queue = c::get("cwt:messagequeue");
        $queue->push(new Message($type,$this,$dest,$data));
    }

    final public function onMessageDispatcher($type,Widget $from,Widget $to,array $data) {
        if ($to == $this) {
            $this->onMessage($type,$from,$data);
            return true;
        } else {
            foreach($children as $child)
                if ($child->onMessageDispatcher($type,$from,$to,$data))
                    return true;
        }
        // No recipient found
        return false;
    }

    public function onMessage($type,Widget $from,array $data) {
        \debug("Widget::onMessage() - type %s from %s", $type, get_class($from));
    }

    public function setPosition($x,$y) {
        $x = (int)$x;
        $y = (int)$y;
        $this->rect->moveTo($x,$y);
    }

    public function getPosition() {
        return [
            $this->rect->x,
            $this->rect->y
        ];
    }

    public function setSize($w,$h) {
        $w = (int)$w;
        $h = (int)$h;
        $this->rect->resizeTo($w,$h);
        $this->onResize($w,$h);
    }

    public function getSize() {
        return [
            $this->rect->w,
            $this->rect->h
        ];
    }

    public function onTick() {

    }

    public function onCreate() {

    }

    public function onDestroy() {

    }

    public function onFocus() {

    }

    public function onBlur() {

    }

    public function onDraw() {
        foreach($this->children as $child)
            $child->onDraw();
    }

    public function onMouseDown($x,$y,$button) {
        if ($this->can_focus)
            $this->postEvent(Message::MSG_FOCUS_CONTROL,null,null);
    }

    public function onMouseUp($x,$y,$button) {}

    public function onKeyDown($key,$shift) {}

    public function onKeyUp($key,$shift) {}

    public function onMeasure() {
        return [null, null];
    }

    public function onResize($width,$height) {
        \debug("%s: onResize %dx%d", __CLASS__, $width,$height);
        $this->rect->resizeTo($width,$height);
    }

    public function onHitTest($x,$y) {
        // Check if the point is in our rect
        if ($this->rect->isIn($x,$y)) {
            // If we have children, let's see if any of them match.
            if (count($this->children) == 0) {
                // But first, if we don't have any children and can receive focus
                // we say so.
                if ($this->can_focus)
                    return $this;
                return null;
            } else {
                // Otherwise we go on to checking our children.
                foreach($this->children as $child) {
                    if (($hit = $child->onHitTest($x-$this->x,$y-$this->y)))
                        return $hit;
                }
            }
        }
        return null;
    }

    public function setParent(Widget $parent) {
        $this->parent = $parent;
    }

    public function getParent() {
        return $this->parent;
    }

    public function getWindow() {
        return $this->window;
    }
}

class Message {
    const
        MSG_FOCUS_CONTROL = 0x01,
        MSG_BLUR_CONTROL = 0x02;
    private $props = [
        'type' => null,
        'src' => null,
        'dest' => null,
        'data' => null
    ];
    public function __construct($type,Widget $source, Widget $dest, Array $data) {
        $this->props = [
            'type' => $type,
            'src' => $source,
            'dest' => $dest,
            'data' => $data
        ];
    }
}
