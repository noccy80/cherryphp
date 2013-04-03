<?php

namespace Cherry\Database;
use ArrayAccess;

use Cherry\Data\Ddl\SdlTag;

class SchemaManager implements ArrayAccess {

    private $db = null;
    private $tables = [];
    private $adapter = null;

    public function __construct(DatabaseConnection $db) {
        $this->db = $db;
        $this->adapter = $db->getSqlAdapter();
        $this->tables = $this->adapter->getTableList();
    }

    public function getAllTables() {
        $out = [];
        $this->tables = $this->adapter->getTableList();
        foreach($this->tables as $table) {
            $out[$table] = $this->adapter->getTable($table);
        }
        return $out;
    }

    public function getTableList() {
        $this->tables = $this->adapter->getTableList();
        return $this->tables;
    }

    public function applyTableSdlString($string) {
        $root = new SdlTag("root");
        $root->loadString($string);
        return $this->applyTableSdl($root->getChild(0));
    }

    public function applyTableSdl(SdlTag $tag) {
        $tm = $this->adapter->getTable($tag[0]);
        if ($tm) {
            $sql = $this->adapter->getAlterFromSdl($tag,$tm);
        } else {
            $sql = $this->adapter->getCreateFromSdl($tag);
        }
        if (empty($sql))
            return false;
        if (is_array($sql)) {
            foreach($sql as $stmt) {
                if (!empty($stmt)) $this->db->execute($stmt);
            }
        } else {
            if (!empty($sql))
                $this->db->execute($sql);
        }
        return true;
    }

    public function generateModel(SdlTag $tag,$namespace = "Models") {
        $model = ($tag->modelname)?$tag->modelname:ucwords($tag[0]).'Model';
        $cols = $tag->query("column");
        $php = "<?php\n\n";
        $php.= "namespace {$namespace};\n\n";
        $php.= "use \\Cherry\\Data\\Model;\n\n";
        $php.= "/** \n";
        $php.= " * @brief Database model.\n";
        $php.= " * \n";
        $php.= " * @generator app-setup 1.0\n";
        $php.= " */\n";
        $php.= "class {$model} extends Model {\n";
        $php.= "\n";
        $php.= "    /// @var Table name\n";
        $php.= "    public \$table = \"{$tag[0]}\";\n";
        $php.= "\n";
        $php.= "    /// @var Columns and types\n";
        $php.= "    public \$columns = [\n";
        foreach($cols as $column) {
            $def = $column->type;
            $php.="        \"{$column[0]}\" => \"{$def}\"".((end($cols)!=$column)?",\n":"\n");
        }
        $php.= "    ];\n";
        $primary = $tag->auto;
        $php.= "\n";
        $php.= "    /// @var Primary index column\n";
        if ($primary)
            $php.= "    public \$primary = \"{$primary}\";\n";
        else
            $php.= "    public \$primary = null;\n";
        $php.= "\n";
        $php.= "    /** \n";
        $php.= "     * Static invoker.\n";
        $php.= "     * \n";
        $php.= "     * @param Mixed \$m Method name\n";
        $php.= "     * @param Array \$a Method arguments\n";
        $php.= "     * \n";
        $php.= "     */\n";
        $php.= "    public static function __callStatic(\$m,\$a) {\n";
        $php.= "        return (new self())->call(\$m,\$a);\n";
        $php.= "    }\n";
        $php.= "\n";
        $php.= "    /** \n";
        $php.= "     * Definitions for the model. Is called when the model is set up.\n";
        $php.= "     */\n";
        $php.= "    public function define() {\n";
        $php.= "        // \$this->setValidator(\"column\", new ValidatorClass());\n";
        $php.= "    }\n";
        $php.= "}\n";
        return $php;
    }

    public function offsetGet($key) {
        try {
            return $this->adapter->getTable($key);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function offsetSet($key,$value) {
    }

    public function offsetExists($key) {
    }

    public function offsetUnset($key) {
    }

}
