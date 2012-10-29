<?php

namespace Ansi {
    class Color {
        const BLACK = 0;
        const RED = 1;
        const GREEN = 2;
        const YELLOW = 3;
        const BLUE = 4;
        const MAGENTA = 5;
        const CYAN = 6;
        const WHITE = 7;
        const DEF = 9;
        private static $colors = array(
            'black' => 0,
            'red' => 1,
            'green' => 2,
            'yellow' => 3,
            'blue' => 4,
            'magenta' => 5,
            'cyan' => 6,
            'white' => 7,
            'default' => 9
        );
        public static function color($name) {
            if (!empty(self::$colors[$name]))
                return self::$colors[$name];
            return null;
        }
        public static function color256($fgcolor=null,$bgcolor=null) {
            if (strpos($fgcolor,',')) $fgcolor = self::rgb256($fgcolor);
            if (strpos($bgcolor,',')) $bgcolor = self::rgb256($bgcolor);
            $seq = "\033[".
                (($fgcolor)?'38;5;'.$fgcolor:'').';'.
                (($bgcolor)?'48;5;'.$bgcolor:'').';';
            $seq = rtrim($seq,';').'m';
            return $seq;
        }
        public static function rgb256($r,$g=null,$b=null) {
            // The first 16 (0 - 15) are the basic ANSI colors.
            if (!$b) list($r,$g,$b) = explode(",",$r);
            if (($r == $g) && ($g == $b)) {
                //The last 24 (232 - 255) are shades of gray.
                $gray = floor($r * (24/255));
                $code = 232 + $gray;
            } else {
                //The values from 16-231 seem to be arranged in an RGB cube.
                // If R, G, and B are each in the range 0 to 5,
                // then color code = 36*R + 6*G + B + 16.
                $r = floor($r * (6 / 255));
                $g = floor($g * (6 / 255));
                $b = floor($b * (6 / 255));
                $code = (36*$r)+(6*$g)+$b+16;
            }
            return $code;
        }
    }
}
namespace Cherry\Cli {

    class Ansi {

        private static $colorstack = null;
        private static $attributes = null;
        private static $colorcur = null;

        const NORMAL = "\033[0m";
        const SET_BOLD = "\033[1m";
        const CLEAR_BOLD = "\033[22m";
        const SET_FAINT = "\033[2m";
        const CLEAR_FAINT = "\033[22m";
        const SET_STANDOUT = "\033[3m";
        const CLEAR_STANDOUT = "\033[22m";
        const SET_UNDERLINE = "\033[4m";
        const CLEAR_UNDERLINE = "\033[24m";
        const SET_BLINK = "\033[5m";
        const CLEAR_BLINK = "\033[25m";
        const SET_REVERSE = "\033[7m";
        const CLEAR_REVERSE = "\033[27m";
        const CLEAR_TO_END = "\033[K";

        public static function init() {
            self::$colorstack = new \Data\FifoQueue();
            self::$attributes = 0x00;
        }

        public static function pushColor($fg,$bg=null) {
            if (self::$colorstack === null) return '';
            $seq = "\033[".($fg+30).(($bg)?';'.($bg+40):'').'m';
            if (self::$colorcur) self::$colorstack->push(self::$colorcur);
            self::$colorcur = $seq;
            return self::$colorcur;
        }

        public static function popColor() {
            if (self::$colorstack === null) return '';
            if (count(self::$colorstack) > 0) {
                self::$colorcur = self::$colorstack->pop();
                return self::$colorcur;
            }
            self::$colorcur = null;
            return self::NORMAL;
        }

        public static function setBold() {
            if (self::$attributes === null) return '';
            return self::SET_BOLD;
        }

        public static function clearBold() {
            if (self::$attributes === null) return '';
            return self::CLEAR_BOLD;
        }

        public static function setUnderline() {
            if (self::$attributes === null) return '';
            return self::SET_UNDERLINE;
        }

        public static function clearUnderline() {
            if (self::$attributes === null) return '';
            return self::CLEAR_UNDERLINE;
        }

        public static function setReverse() {
            return self::SET_REVERSE;
        }

        public static function clearReverse() {
            return self::CLEAR_REVERSE;
        }

        public static function pushEffect($effect) {
            if (self::$attributes === null) return '';
        }

        public static function popEffect() {
            if (self::$attributes === null) return '';
        }

        public static function color($string,$color,$bgcolor=null) {
            return self::pushColor(\Ansi\Color::color($color),\Ansi\Color::color($bgcolor)).$string.self::popColor();
        }

        public static function uncolor($string) {

        }

        public static function colorStrip($str) {

        }

        public static function reset() {
            if (self::$attributes === null) return '';
            self::$attributes = 0x00;
            return self::NORMAL;
        }

        public static function clearToEnd() {
            return self::CLEAR_TO_END;
        }

    }

}
