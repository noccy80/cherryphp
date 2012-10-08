<?php

namespace Cherry\Cwt\Widgets;

class Menubar extends Widget {

    const EVT_CLICK = 'cwt:menubar.click';

    public function hittest($x,$y) { }

    public function update() { }

    public function addMenu($id, $label, Menu $menu) { }

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
