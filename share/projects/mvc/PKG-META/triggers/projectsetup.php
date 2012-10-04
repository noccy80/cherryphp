<?php

use Cherry\unipath;
use Cherry\Base\Event;

\Cherry\BundleManager::load('cherry.crypto');

// Register a post-setup hook for the CherryUtil application command. This will be
// triggered when the files have been copied.
Event::observe('cherryutil.application.post-setup', function(array $data, $log) {
    
    $app_root = $data['approot'];
    
    $cfgpath = \cherry\unipath($app_root.'/application.ini');
    if (!file_exists($cfgpath)) {
        
        if ($log) $log->write("Application config not found. Creating...\n");
        $cfg = array(
            'application' => array()            
        );
        
    } else {

        $log->write("Updating application configuration...\n");
        $cfg = parse_ini_file($cfgpath,true);

    }

    $cfg['application']['namespace'] = $data['appns'];
    if (empty($cfg['application']['appname'])) $cfg['application']['appname'] = 'MVC Application';
    if (empty($cfg['application']['version'])) $cfg['application']['version'] = '1.0.0';
    if (empty($cfg['application']['uuid']) || $data['replace']) {
        printf("Generating new UUID...");
        $uuid = \cherry\crypto\Uuid::getInstance()->generate(\cherry\crypto\Uuid::UUID_V4);
        //$uuid = trim(exec('uuidgen'));
        printf("%s\n", $uuid);
        $cfg['application']['uuid'] = $uuid;
    } else {
        $log->write("UUID already set. If you wish to regenerate it, use +replace.\n");
    }
    
    $out = '';
    foreach($cfg as $group=>$cfgvals) {
        $out.=sprintf("[%s]\n", $group);
        foreach($cfgvals as $k=>$v) {
            if (is_string($v)) $v = '"'.$v.'"';
            $out.=sprintf("%s=%s\n", $k,$v);
            if ($cfgvals[$k] == end($cfgvals)) $out.="\n";
        }
    }
    file_put_contents($cfgpath,$out);
    
    // Do the actual configuration
    printf("Applying templates...\n");
    
});
