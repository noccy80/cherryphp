<?php

namespace Cherry\Core;

use Cherry\Crypto\UuidGenerator;

/**
 * This class provides functionality for objects
 *
 *
 *
 */
abstract class Object {
    
    private $_object_uuid = null;
    
    /**
     * Get (or generate a new) the UUID for this object.
     *
     * @return string The assigned (or generated) UUID for the object.
     */
    public function getUuid() {
        if (empty($this->_object_uuid))
            $this->_object_uuid = UuidGenerator::v4();
        return $this->_object_uuid;
    }
    
    /**
     * Assign the UUID for this object. Set it to null to clear it in order to have
     * it regenerated on the next call to getUuid().
     *
     * @param mixed $uuid The UUID to assign (or null to regenerate).
     */
    public function setUuid($uuid=null) {
        if ($uuid === null) {
            $this->_object_uuid = null;
            return true;
        }
        if (!UuidGenerator::valid($uuid))
            throw new \UnexpectedValueException("Assigned value is not a valid UUID: {$uuid}");
        $this->_object_uuid = $uuid;
        return true;
    }
    
}