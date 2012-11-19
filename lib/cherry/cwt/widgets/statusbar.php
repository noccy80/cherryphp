<?php

namespace Cherry\Cwt\Widgets {

    class Statusbar extends \Cherry\Cwt\Widgets\Widget {

        const SBR_ALIGN_LEFT = 0;
        const SBR_ALIGN_CENTER = 1;
        const SBR_ALIGN_RIGHT = 2;

        const SBR_WIDTH_AUTO = -1;
        const SBR_WIDTH_EXPAND = 0;

        private $items = array();

        public function addItem($id, \Cherry\Cwt\Widgets\Statusbar\BarItem $item, $width = self::SBR_WIDTH_AUTO, $align = self::SBR_ALIGN_LEFT) {
            $this->items[$id] = (object)array(
                'item' => $item,
                'align' => $align,
                'width' => $width
            );
        }

        public function draw() {
            ncurses_color_set(1);
            ncurses_mvwaddstr($this->wnd(),0,0,str_repeat(" ",$this->width));
            // Measure items
            $fullwidth = $this->width;
            $numauto = 0;
            $alloc = 0;
            foreach($this->items as $key=>$item) {
                $item->item->update();
                $itemwidth = $item->item->measure();
                if ($item->width == self::SBR_WIDTH_AUTO) {
                    $numauto++;
                } elseif ($item->width == self::SBR_WIDTH_EXPAND) {
                    $alloc+= $itemwidth;
                } else {
                    $alloc+= $item->width;
                }
            }
            // Count in the separator bars
            $alloc += (count($this->items) - 1) * 3;
            // With the items measured, hand out the auto space
            if ($numauto>0) {
                $autowidth = floor(($fullwidth - $alloc) / $numauto);
            }
            // Now draw it all
            $cptr = 1;
            $idx = 0;
            foreach($this->items as $key=>$item) {
                if ($item->width == self::SBR_WIDTH_AUTO) {
                    $itemwidth = $autowidth;
                } elseif ($item->width == self::SBR_WIDTH_EXPAND) {
                    $itemwidth = $item->measure();
                } else {
                    $itemwidth = $item->width;
                }
                $cstr = substr($item->item->value,0,$itemwidth);
                ncurses_color_set(1);
                ncurses_mvwaddstr($this->wnd(),0,$cptr, $cstr);
                $idx++;
                $cptr += $itemwidth;
                if ($idx != count($this->items)) {
                    ncurses_color_set(2);
                    ncurses_mvwaddstr($this->wnd(),0,$cptr,' | ');
                }
                $cptr += 3;
            }

            ncurses_color_set(0);
        }

        public function hittest($x,$y) {
            return $this;
        }

        public function onClick($b,$x,$y) {
            $this->emit(Widget::ON_CLICK,$b,$x,$y);
            ncurses_mvaddstr(13,5,sprintf("StatusBar onClick: %dx%d (button %d)    ", $x, $y, $b));
        }

        public function update() { }

    }

}

namespace Cherry\Cwt\Widgets\Statusbar {

    class BarItem {

        const WIDTH_AUTO = -1;

        const TYPE_TEXT = 'text';
        const TYPE_CLOCK = 'clock';
        const TYPE_DATASOURCE = 'datasource';

        protected $props = array(
            'data' => null,
            'bold' => false,
            'datasource' => null,
            'value' => null
        );

        public function hitTest($x,$y) {
            return $this;
        }

        public function __construct($type,$data) {
            $this->props['type'] = $type;
            if ($type == BarItem::TYPE_DATASOURCE) {
                $this->props['datasource'] = $data;
            } else {
                $this->props['data'] = $data;
            }
        }

        public function update() {
            switch($this->type) {
                case BarItem::TYPE_TEXT:
                    $this->props['value'] = $this->props['data'];
                    break;
                case BarItem::TYPE_CLOCK:
                    $this->props['value'] = date($this->props['data']);
                    break;
                case BarItem::TYPE_DATASOURCE:
                    if (is_callable($this->props['datasource'])) {
                        $this->props['value'] = call_user_func($this->props['datasource']);
                    } elseif (is_array($this->props['datasource'])) {
                        list($obj,$prop) = $this->props['datasource'];
                        $this->props['value'] = $obj->{$prop};
                    } else {
                        $this->props['value'] = 'Invalid datasource for panel';
                    }
                    break;
            }
        }

        public function __get($key) {
            if (array_key_exists($key,$this->props)) {
                return $this->props[$key];
            }
        }

        public function __set($key,$value) {
            if (array_key_exists($key,$this->props)) {
                $this->props[$key] = $value;
            }
        }

        public function measure() {
            return array(strlen($this->value),1);
        }

        public function render($row,$col) {

        }

    }

}
