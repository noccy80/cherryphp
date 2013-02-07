# Xenon: PHP BootStrapper

Cut down on the boilerplate and bootstrap your code with just two lines of code!

    require_once('xenon/xenon.php');
    xenon\xenon::framework('yourframework');


## The `xenon\xenon` class

### `xenon\xenon::framework($framework,$version=null)`

Load the specified framework.

### `xenon\xenon::config($key)`

Get a configuration value.

### `xenon\xenon::config($key,$value)`

Set a configuration value.

### `xenon\xenon::config(array $values)`

Set several configuration values at once

## Configuration available to frameworks

### framework.debuglevel

Set the debuglevel for the code being executed.

 * `-1` - No change
 * `0` - Explicitly disable debugging
 * `1` - Enable basic debugging
 * `2` - Enable verbose debugging
 
### framework.preload

Classes to autoload after autoloading the framework.