<?php

namespace Cherry\Wx;

abstract class WxApplication extends \wxApp implements \Cherry\Core\IApplication {

    public $appname = "Application name";
    public $appversion = "1.0.0";
    public $appdescription = "Application description";
    public $appauthors = null;
    public $appcopyright = null;

    abstract public function main();

    function OnInit() {
        return $this->main();
    }

    function OnExit() {
        return 0;
    }

    public function run() {
        \wxApp::SetInstance($this);
        wxEntry();
    }
    
    public function showAbout(\wxFrame $frame) {
        $msg = "{$this->appname} v{$this->appversion}\n\n";
        $msg.= trim(wordwrap($this->appdescription,40))."\n";
        if ($this->appauthors) $msg.= "\n{$this->appauthors}";
        if ($this->appcopyright) $msg.= "\n{$this->appcopyright}";
        $phpver = PHP_VERSION;
        $phppf = PHP_OS;
        $msg.= "\n\nRunning on PHP {$phpver} ({$phppf})";
        $dlg = new \wxMessageDialog($frame,
            $msg,"About {$this->appname}",\wxICON_INFORMATION|\wxOK);
        $dlg->ShowModal();
    }

}

