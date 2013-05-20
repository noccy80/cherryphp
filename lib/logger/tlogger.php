<?php

namespace Logger;

trait TLogger {
    
    protected function logEmerg($str) {
        Logger::log(LOG_EMERG, $str, 1);
    }        

    protected function logAlert($str) {
        Logger::log(LOG_ALERT, $str, 1);
    }        

    protected function logCrit($str) {
        Logger::log(LOG_CRIT, $str, 1);
    }        

    protected function logErr($str) {
        Logger::log(LOG_ERR, $str, 1);
    }        

    protected function logWarning($str) {
        Logger::log(LOG_WARNING, $str, 1);
    }        

    protected function logNotice($str) {
        Logger::log(LOG_NOTICE, $str, 1);
    }        

    protected function logDebug($str) {
        Logger::log(LOG_DEBUG, $str, 1);
    }
    
    protected function logInfo($str) {
        Logger::log(LOG_INFO, $str, 1);
    }
    
}