<?php

/*
//LOADER:BEGIN
require('cherryphp.php');
//LOADER:END
*/

//LOADER:BEGIN
if (!( @include_once "lib/bootstrap.php" )) {
    $libpath = getenv('CHERRY_LIB');
    if (!$libpath) {
        fprintf(STDERR,"Define the CHERRY_LIB envvar first.");
        exit(1);
    }
    require_once($libpath.'/lib/bootstrap.php');
}
//LOADER:END

App::bootstrap([
    'app.ns' => 'CherryTree',
    'app.public' => dirname(__FILE__),
    'app.root' => dirname(dirname(__FILE__))
]);
//App::extend('profiler',new AppProfiler('perf.log'));
//App::profiler()->setReporting(AppProfiler::REPORT_FULL);
//App::profiler()->push('Loading bundles...');
App::bundles()->load('cherry.mvc');
//App::profiler()->pop();
App::router()->addRoutes([
    '/admin/posts/(:str)' => 'admin/posts/$1',
    '/debug' => 'default/debug',
    '/test' => 'default/test',
    '/post/(.*)' => 'default/view:$1',
    '/(:str)/(:str)' => 'default/index:$1,$2',
    '/(:str)' => 'default/index:$1',
    '/' => 'default/index'
]);
App::router()->addPassthru([
    '/js/*' => 'public',
    '/css/*' => 'public',
    '/favicon*' => 'public'
]);
App::router()->route();

class AppProfiler {
    private
            $target = null,
            $start = null,
            $prestart = null,
            $log = [],
            $stack = [],
            $meminitial = null,
            $meminitialfull = null,
            $reportinglevel = null;
    const
            LOGEVT_ENTER = '->:',
            LOGEVT_LEAVE = '<-:',
            LOGEVT_LOG = '  :';
    const
            REPORT_NONE = null,
            REPORT_SUMMARY = 'summary',
            REPORT_FULL = 'full';


    public function enter($module) {
        $this->push($module);
        return new \Cherry\Util\ScopedObserver([ $this, 'pop' ]);
    }
    public function push($module) {
        $this->log[] = [ microtime(true), count($this->stack), self::LOGEVT_ENTER, $module];
        array_push($this->stack, $module);
    }
    public function pop() {
        $module = array_pop($this->stack);
        $this->log[] = [ microtime(true), count($this->stack), self::LOGEVT_LEAVE, '('.$module.')'];
    }
    public function log($msg) {
        $this->log[] = [ microtime(true), count($this->stack), self::LOGEVT_LOG, $msg];
    }
    public function __construct($target=null) {
        $this->target = $target;
        if (!empty($_SERVER['REQUEST_TIME_FLOAT']))
            $this->prestart = floatval($_SERVER['REQUEST_TIME_FLOAT']);
        $this->start = microtime(true);
        $this->meminitial = memory_get_usage();
        $this->meminitialfull = memory_get_usage(true);
        define('IS_PROFILING',true);
    }
    public function setReporting($level = self::REPORT_SUMMARY) {
        $this->reportinglevel = $level;
    }
    public function __destruct() {
        if (!$this->target) return;
        $out = [];
        $out[] = 'Profiling Log:';
        $out[] = '';
        if ($this->prestart) {
            $out[] = sprintf('%10sms | %-3s %s','-'.number_format(($this->start - $this->prestart)*1000,4), self::LOGEVT_LOG, 'Request begin');
        }
        $out[] = sprintf('%10sms | %-3s %s', number_format(0,4), self::LOGEVT_LOG, 'Profiling application');
        foreach($this->log as $event) {
            list($time,$level,$type,$message) = $event;
            $ts = ($time - $this->start)*1000;
            $out[] = sprintf('%10sms | %-3s %s', '+'.number_format($ts,4), $type, str_repeat(' · ',$level+1).$message);
        }
        $end = microtime(true);
        $memend = memory_get_peak_usage();
        $memendfull = memory_get_peak_usage(true);
        $out[] = '';
        $foot = [
            sprintf('Execution time: %.4fms (Total: %.4fms)', ($end - $this->start)*1000, ($end - $this->prestart)*1000),
            sprintf('Memory usage: init=%.2fKB peak=%.2fKB (+%.2fKB) (true=%.2fKB peak=%.2fKB)',
                         $this->meminitial/1024, $memend/1024, ($memend - $this->meminitial)/1024,
                         $this->meminitialfull/1024, $memendfull/1024)];
        $out = array_merge($out, $foot);
        $out[] = '';
        $logfile = join("\n",$out);
        if (php_sapi_name() == 'cli')
            fprintf(STDERR, $logfile);
        elseif ($this->reportinglevel == self::REPORT_FULL)
            foreach($out as $line) error_log($line);
        elseif ($this->reportinglevel == self::REPORT_SUMMARY)
            foreach($foot as $line) error_log($line);
        else
            file_put_contents($this->target,$logfile);
    }
}
