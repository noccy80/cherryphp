<?php

namespace Cherry\Db;

/**
 * @brief Connection pool for database connections.
 *
 */
class ConnectionPool {
    
    /// @var Connection is for reading only.
    const CONN_READ = 0x01;
    /// @var Connection is for writing only.
    const CONN_WRITE = 0x02;
    /// @var Connection is for reading and writing.
    const CONN_READWRITE = 0x03; // CONN_READ | CONN_WRITE
    /// @var Connection is buffered, write is delayed until the destruction
    ///         of the connection.
    const CONN_BUFFERED = 0x04;
    
    public static function getInstance() {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }
    
    /**
     * @brief Returns a list of the registered connection groups.
     *
     * Each entry in the list is an assoc array holding keys for the name of the
     * group, the group access mode, and some additional details.
     *
     * @return array The group list
     */
    public function getGroups() {
        
    }
    
    /**
     *
     */
    public function getConnection($group, $flags = self::CONN_READWRITE) {
    
    }
    
}

class DatabaseConnection {
    
}