<?php

namespace Cherry\Expm\Mvc;

use Cherry\Web\Request;
use Cherry\Web\Response;
use Cherry\Base\PathResolver;

/*
 * class Controller
 */

class Controller {
    protected $request = null;
    protected $response = null;
    protected $params = [];
    public function __construct(Request $request, Response $response, array $params) {
        $this->request = $request;
        $this->response = $response;
        $this->params = $params;
    }
    public function loadView($foo) {
        $path = PathResolver::getInstance()->getPath("{APP}/views/{$foo}");
        require $path;
    }
}
