<?php

namespace Cherry\Expm\Mvc;

use Cherry\Web\Request;
use Cherry\Web\Response;
use Cherry\Base\PathResolver;

/**
 * class Controller
 */ 
class Controller {

    protected $request = null;
    protected $response = null;
    protected $params = [];

    /**
     *
     *
     * @param Request $request The request object
     * @param Response $response The response object
     * @param array $params The parameters
     */
    public function __construct(Request $request, Response $response, array $params) {
        $this->request = $request;
        $this->response = $response;
        $this->params = $params;
    }

    /**
     *
     *
     *
     *
     */
    public function loadView($view) {
        $path = PathResolver::getInstance()->getPath("{APP}/views/{$view}");
        require $path;
    }

}
