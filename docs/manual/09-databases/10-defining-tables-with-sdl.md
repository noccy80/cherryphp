




## Defining a table


    table "foo" auto="fooid" {
        column "fooname" type="text:256" default="Unnamed foo"
    }

Valid column types:

 * `text` or `char` is a case insensitive string of an arbitrary length, which maps to `VARCHAR`, `TEXT` or `MEDIUMTEXT`. If you are looking
    for a case sensitive field, you should check `binary`.
 * `binary` is case sensitive data. It maps to `VARBINARY`, `BLOB` or `MEDIUMBLOB`.
 * `bool` translates to a `INT` or `TINYINT` with either a 0 or a 1 for value,
    representing false and true.
 * `int` is an integer. Maps to `TINYINT`, `SMALLINT`, `INT` or `BIGINT`
 * `float` is a float. Maps to `FLOAT` or `REAL`.
 * `double` is a double, which maps to `DOUBLE` or `FLOAT`.
 * `decimal` is mapped to `DECIMAL` or `TEXT`.

Column attributes:

 * `type` is the field type. Note: You can also specify this on a per-database
    type basis, like `mysqltype` and `sqlitetype`. These types are however used
    verbatim, so they need to be specified per the SQL standard; ex. `TINYINT(2)`
    or `TEXT(128)`.
 * `default` gives the column a default value.
 * `null` defines if the column can be null; default is `false` meaning all
    columns are defined as `NOT NULL` (SQL) unless `null=true`.
 * `references` adds a relationship for use by models.

Relationships:

    table "first" auto="fooid" {
        column "fooname" type="text:128"
        /**
         * one-to-many, reference all. "second.barid:1" would make
         * it a one-to-one. The column will automatically become a
         * INT column.
         */
        column "bars" references="second.barid:*"
        data {
            columns "fooid" "bars"  "fooname"
            values  1       "1"     "foofoo"
        }
    }
    table "second" auto="barid" {
        column "barid" type="int"
        column "barname" type="text:128"
        data {
            columns "barid" "barname"
            values  1       "foobar"
            values  2       "bazbar"
        }
    }

With the firstmodel, this is now valid:

    $first = FirstModel::find(1); // Get foofoo.
    foreach($first->bars as $bar) { // Only got foobar so far.
        echo $bar->barname . "\n";
    }
    // Add bazbar to the bars of foo
    $first->bars->add(SecondModel::find(2));

Helpful hints:

 * You don't have to define the auto field in the column list. If the field
    set as the auto field in the `table` tag is not defined, it will be added
    to the top of the column list as an appropriate autonumber field.
 * The different databases has different limitations. SQLite does not support
    renaming or removing columns from a table, and thus removed or renamed
    columns in the definition will cause the table to be dropped and recreated.
    Therefore, only use SQLite for prototyping or in situations where a local
    database exist and will not be changed. In the future this function can
    be made to copy the existing data into the new table.
