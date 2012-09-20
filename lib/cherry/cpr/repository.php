<?php

namespace Cherry\Cpr;

class RepositoryList implements \IteratorAggregate {

    private $con;
    private $reporoot;
    private $repolist;
    private $repomanifest;

    function __construct($list='local') {
        $this->con = \Cherry\Cli\Console::getConsole();
        switch($list) {
            case 'local':
                $reporoot = getenv('HOME').'/.cherryphp/cpa';
                if (!file_exists($reporoot)) {
                    $this->initialize($reporoot);
                }
                break;
            case 'global':
                $reporoot = CHERRY_LIB.'/cpa/';
                if (!file_exists($reporoot)) {
                    $this->initialize($reporoot);
                }
                break;
        }
        $this->reporoot = $reporoot;
        $this->repomanifest = $this->reporoot.'/repositories.json';
        $this->loadManifest();
    }
    
    function __destruct() {
        $this->saveManifest();
    }
    
    function addRepository($url) {
        $this->con->write("Adding repository %s...\n", $url);
        $this->manifest->repositories[] = $url;
    }
    
    function loadManifest() {
        $this->manifest = json_decode(file_get_contents($this->repomanifest));
        $this->con->write("Loaded repositorylist from %s:\n", $this->reporoot);
        $this->con->write("Repositories: %d, Packages: %d (%d installed)\n", 0, 0, 0);
    }
    
    function saveManifest() {
        $this->con->write("Saving manifest...\n");
        $json = json_encode($this->manifest);
        file_put_contents($this->repomanifest, $json);
    }
    
    function initialize($path) {

        $this->con->write("Initializing local CPR repository...\n");
        mkdir($path,0777,true);
        $manifest = array(
            'type' => 'cherryphp/local-cpr',
            'packages' => array(),
            'repositories' => array()
        );
        file_put_contents($path.'/repositories.json', json_encode($manifest));

    }
    
    function getIterator() {
    
        if (empty($this->repolist)) {
            $this->repolist = array();
            foreach($this->manifest->repositories as $repo) {
                $this->repolist[] = new Repository($repo,$this);
            }
        }
        
        return new \ArrayIterator($this->repolist);
    
    }
}

class Repository {

    private $url = null;
    private $rl = null;
    private $con = null;

    function __construct($url,RepositoryList $repolist) {
        $this->con = \Cherry\Cli\Console::getConsole();
        $this->url = $url;
        $this->rl = $repolist;
    }
    
    function update() {
        $this->con->write("Updating %s...\n", $this->url);
    }

}
