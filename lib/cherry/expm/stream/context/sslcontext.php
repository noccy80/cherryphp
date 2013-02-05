<?php

namespace Cherry\Expm\Stream\Context;

class SslContext extends StreamContext {
 
    public function __construct(array $context=null) {
        parent::__construct('ssl',(array)$context);
    }
    
    public function setVerifyPeer($bool) {
        $this->context['ssl']['verify_peer'] = $bool;
    }
    
}