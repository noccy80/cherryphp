<?php

namespace Cherry\Net\Transport;

/**
 * @brief Base class for transports.
 *
 * This is supposed to be entirely transparent, and optionally bidirectional.
 *
 */
abstract class Transport {

    protected
            $buffer_in = [],
            $buffer_out = [],
            $state = [],
            $upgrade_transport = null;
   
    /**
     * @brief Check if the transport is bidirectional
     *
     * @return bool True if the transport is bidirectional.
     */
    abstract function getTransportBidir();

    /**
     * @brief Set up the transport.
     *
     * This method should take care of handshaking etc that is needed to get
     * things going.
     *
     * @return bool True if the transport started up Ok.
     */
    abstract function begin($state);
    
    /**
     * @brief Write a block/frame of data.
     *
     * @param string $data The data to write.
     * @return bool True if the data was written Ok.
     */
    public function write($data) {
        $this->buffer_out[] = $data;
    }
    
    /**
     * @brief Read a block/frame of data.
     *
     * Make sure to test this like so:
     * @code
     *      $data = $t->read();
     *      if ($data !== false) { ... }
     * @endcode
     *
     * @return Mixed Data frame or false.
     */
    public function read() {
        if (count($this->buffer_in)>0)
            return array_shift($this->buffer_in);
        return false;
    }
    
    public function canUpgrade() {
        return $this->upgrade_transport;
    }
    
    /**
     * @brief Upgrade the transport.
     * 
     * @code
     *  // If we have a socket transport
     *  $t = SocketTransport();
     *  // Looking at the data, we can determine it's a HTTP request
     *  if ($t->canUpgrade()) {
     *      // By doing this we migrade the SocketTransport into a HttpTransport
     *      // as determined by the SocketTransport.
     *      $t = $t->upgrade();
     *      // And after doing some more fidgeting, we can upgrade to a websocket
     *      if ($t->canUpgrade()) {
     *          $t = $t->upgrade();
     *      }
     *  }
     *  if ($t instanceof WebsocketTransport) {
     *      // Do websockety goodness.
     *  }
     * @endcode
     *
     * Note that due to how this function works, the sockets must be closed by
     * something other than the transport or the result may be unreliable.
     *
     * Note also that upgrading is optional. You can still work with the current
     * transport to emulate a specific behavior.
     *
     */
    public function upgrade() {
        $c = new $upgrade();
        $c->begin($this->state);
        return $c;
    }

}

class HttpTransport extends Transport {
    
    public function getRequest() { return $this->get('request'); }
    public function getResponse() { return $this->get('response'); }
    
    public function getTransportBidir() { return false; }
    public function begin($state) {
        parent::begin($state);
        // Do begin stuff
        $this->upgrade_transport = 'WebsocketTransport';
    }
    
}

class WebsocketTransport extends HttpTransport {
    
}