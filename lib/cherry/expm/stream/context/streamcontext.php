<?php

namespace Cherry\Expm\Stream\Context;

class StreamContext {

    protected $context = [];

    public function __construct($type, array $context) {
        $this->context[$type] = (array)$context;
    }

    public function addContext(StreamContext $context) {
        $this->context = array_merge($this->context,$context->getContextData());
    }
    
    public function getContextData() {
        return $this->context;
    }
    
    public function getContext(array $params) {
        return stream_context_create($this->context, $params);
    }
    
}