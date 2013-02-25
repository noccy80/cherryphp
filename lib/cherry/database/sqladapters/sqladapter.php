<?php

namespace Cherry\Database\SqlAdapters;

use Cherry\Data\Ddl\SdlTag;

/*
 * class SqlAdapter
 */
abstract class SqlAdapter {
    abstract public function getAlterFromSdl(SdlTag $tag, $meta);
    abstract public function getCreateFromSdl(SdlTag $tag);
    abstract public function getTableList();
    abstract public function getTable($table);
}
