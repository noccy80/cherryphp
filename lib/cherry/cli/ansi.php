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
        public static function color256($fgcolor=null,$bgcolor=null) {
            $seq = "\033[".
                (($fgcolor)?'38;5;'.$fgcolor:'').';'.
                (($bgcolor)?'48;5;'.$bgcolor:'').';';
            $seq = rtrim($seq,';').'m';
            return $seq;
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

        function pushColor($fg,$bg=null) {
            if (self::$colorstack === null) return '';
            $seq = "\033[".($fg+30).(($bg)?';'.($bg+40):'').'m';
            if (self::$colorcur) self::$colorstack->push(self::$colorcur);
            self::$colorcur = $seq;
            return self::$colorcur;
        }

        function popColor() {
            if (self::$colorstack === null) return '';
            if (count(self::$colorstack) > 0) {
                self::$colorcur = self::$colorstack->pop();
                return self::$colorcur;
            }
            self::$colorcur = null;
            return self::NORMAL;
        }

        function setBold() {
            if (self::$attributes === null) return '';
            return self::SET_BOLD;
        }

        function clearBold() {
            if (self::$attributes === null) return '';
            return self::CLEAR_BOLD;
        }

        function setUnderline() {
            if (self::$attributes === null) return '';
            return self::SET_UNDERLINE;
        }

        function clearUnderline() {
            if (self::$attributes === null) return '';
            return self::CLEAR_UNDERLINE;
        }

        function pushEffect($effect) {
            if (self::$attributes === null) return '';
        }

        function popEffect() {
            if (self::$attributes === null) return '';
        }

        function color($color,$string) {

        }

        function uncolor($string) {

        }

        function colorStrip($str) {

        }

        function reset() {
            if (self::$attributes === null) return '';
            self::$attributes = 0x00;
            return self::NORMAL;
        }

        function clearToEnd() {
            return self::CLEAR_TO_END;
        }

    }

}
