<?php

namespace Cherry\Database\SqlAdapters;

use Cherry\Data\Ddl\SdlTag;

/**
 * @class SqlAdapter
 */
abstract class SqlAdapter {

    /**
     * Get an alter statement from the provided tag
     */
    abstract public function getAlterFromSdl(SdlTag $tag, $meta);

    /**
     * Get a create statement from the provided tag
     */
    abstract public function getCreateFromSdl(SdlTag $tag);

    abstract public function getCreateTableSql($table);

    /**
     * Get a plain list of all the available tables
     */
    abstract public function getTableList();

    /**
     * Get metadata for a specific table
     */
    abstract public function getTable($table);

    /**
     * Convert a type attribute to the appropriate SQL type.
     */
    abstract protected function getSqlVartype(SdlTag $tag);

}
