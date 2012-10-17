<?php

namespace Cherry\Cwt\Widgets;

class Menubar extends Widget {

    const EVT_CLICK = 'cwt:menubar.click';

    private $menus = array();

    public function hittest($x,$y) { }

    public function update() { }

    public function addMenu($id, $label, Menu $menu) {
        $this->menus[$id] = (object)array(
            'label' => $label,
            'menu' => $menu
        );

    }

    public function draw() {
        ncurses_color_set(1);
        ncurses_mvaddstr($this->top,$this->left,str_repeat(" ",$this->width));
        $yp = 1;
        foreach($this->menus as $id=>$data) {
            $str = ' '.str_replace('_','',$data->label).' ';
            $ofs = strpos($data->label,'_');
            ncurses_mvaddstr($this->top,$this->left + $yp,$str);
            if ($ofs!==false) {
                ncurses_attron(NCURSES_A_UNDERLINE);
                $hlchar = substr($data->label,$ofs + 1,1);
                ncurses_mvaddstr($this->top,$this->left + 1 + $yp + $ofs, $hlchar);
                ncurses_attroff(NCURSES_A_UNDERLINE);
            }
            $yp+= strlen($data->label)+2;
        }
        ncurses_color_set(0);
    }

}

class Menu {

    public function addItem(MenuItem $item) {

    }

    public function addSeparator() {

    }

}

class MenuItem {

    const MENU_DISABLED = 0x01;
    const MENU_TOGGLE = 0x02;

    public function __construct($id,$label,$flags=0x00,$hotkey=null) {

    }

}
