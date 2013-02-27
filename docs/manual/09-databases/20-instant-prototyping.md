
# Instant prototyping with Cherry and Neon

Using the Neon application server you can instantly prototype and test out your
databases. After creating an application, the `app-bin` folder should contain a
script named `setup` which helps set up the application on the system, including
creation of databases (and users), generating keys for the application, initializing
the keystore etc. This is done by parsing configuration files contained in `config/`.
For the database, this file should be named `database.sdl` and it is the same file
that is being used by Cherry to store the database connections.

To prototype your tables, add a new table definition to your `database.sdl`. The
comments will be used to set up the databases and tables for the database backends
that support it.

    // Database of colorful fruit
    table "fruit" auto="fruitid" {
        column "name" type="text:64"
        column "color" type="text:64"
    }

Now, run the setup script for the database:

    $ app-bin/setup database
    Reconfiguring application...
    Checking setup shim databaseSetupShim ... Ok
    Running setup shim databaseSetupShim
    Reading configuration for database...
    Checking table fruits ... Table created
    Generating models...
    Generating model for fruits ... FruitDb\Models\FruitsModel
    1 models written to fruitdb/models
    $

But we forgot that we also need to track the size of the fruit. So we update the
definitions:

    // Database of colorful fruit
    table "fruit" auto="fruitid" {
        column "name" type="text:64"
        column "color" type="text:64"
        column "size" type="enum" {
            value "SMALL"
            value "MEDIUM"
            value "LARGE"
            value "HUGE"
        }
    }

Re-running the setup script will add the missing columns while leaving the data
intact:

    $ app-bin/setup database
    Reconfiguring application...
    Checking setup shim databaseSetupShim ... Ok
    Running setup shim databaseSetupShim
    Reading configuration for database...
    Checking table fruits ... Table updated
    Generating models...
    Generating model for fruits ... FruitDb\Models\FruitsModel
    1 models written to fruitdb/models
    $

To fetch the data, call on your model:

    FruitsModel::create([
        'name' => 'banana',
        'color' => 'yellow',
        'size' => 'MEDIUM'
    ]);
    $yellowfruit = FruitsModel::findByColor("yellow")->getFirst();
    echo "A nice yellow fruit is the {$yellowfruit->name}.";

You can also add conditions to the query:

    $greenfruit = FruitsModel::findByColor("green")->andSize("SMALL")->getFirst();

This should give you something like:

    A nice yellow fruit is the banana.
