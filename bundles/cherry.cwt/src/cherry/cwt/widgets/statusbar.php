<?php

namespace Cherry\Cwt\Widgets {

    class Statusbar {

        const TBR_ALIGN_LEFT = 0;
        const TBR_ALIGN_CENTER = 1;
        const TBR_ALIGN_RIGHT = 2;

        const TBR_WIDTH_AUTO = -1;

        public function addItem(Item $item, $align = self::TBR_ALIGN_LEFT, $width = self::TBR_WIDTH_AUTO) {

        }

    }

}

namespace Cherry\Cwt\Widgets\Statusbar {

    class Item {

        protected $props = array(
            'label' => null,
            'bold' => false,
            'datasource' => null
        );

        public function __get($key) {
            if ($key == 'value') {
                if (!empty($this->props['datasource'])) {
                    return $this->props['datasource']();
                } else {
                    return $this->props['label'];
                }
            }
        }

        public function __set($key,$value) {

        }

        public function measure() {
            $lbl = $this->label;
            return array(strlen($lbl),1);
        }

        public function render($row,$col) {

        }

    }

}
