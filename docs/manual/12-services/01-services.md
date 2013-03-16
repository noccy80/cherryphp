@chapter 12
@title Services, Objects and Managers

# Services!

Services! Everybody loves them! The definition of a service is an instance of
something that can be started and stopped while running asynchronously from the
main user interface. And in Cherry, they are dead easy to use.

## A basic service

    class TestService extends ServiceInstance {
        public $serviceid = "info.noccylabs.testservice";
        protected $flags = ServiceInstance::SVC_RESTART;
        function servicemain() {
            for($s = 0; $s < 5; $s++) {
                usleep(100000);
            }
        }
    }

If we don't want to access the service class directly, or want to leave it
running on its own, we register it with the ServiceManager`:

    // Register in the service manager
    SvcMan::addServiceInstance(new TestService("/tmp/testservice.pid"));

This object would now be available as `local:/services/info.noccylabs.testservice`.

    // Or register a permanent instance
    $uuid = SvcMan::addPersistentService("\\TestService");
    $svc = ObjMan::getObject("local:/services/{$uuid}");
    $svc->cloneable = true;

The permanent instance would be accessible via URIs similar to these:

 * `local:/services/info.noccylabs.testservice#08dcd373-9fda-4ebb-8411-dbf7659d9b59`
 * `local:/services/08dcd373-9fda-4ebb-8411-dbf7659d9b59`

## The `ServiceManager` and the `ObjectManager`

Let's first take a look at how we would get an instance of a service and do
something with it. First, we need to make sure the service manager is loaded
and registered with the object manager:

    use Cherry\Core\ServiceManager as SvcMan;
    use Cherry\Core\ServiceInstance;
    SvcMan::register();

To get our instance, we request it from the `/services/` object path, either
by id or uuid:

    $svc = ObjMan::getObject("local:/services/info.noccylabs.testservice#0");

Let's break that down for a second. The object URI consist of these parts:

     local: /services/ info.noccylabs.testservice #0
    '--.---'----.-----'-----.--------------------'.-'
       |        |           |                     |
    Object Root |      Object name          Object Instance
           Object Path

 * **Object root** is always local for now. but it is intended to be able to
   point at another `ObjectManager` - local or remote - to manage objects.
 * **Object path** is the path to the virtual directory that the object
   resides in. it is always absolute and should begin and end with a `/`.
 * **Object name** is the name of the object.
 * **Object instance** is optional and denotes that the request is interested
   in a specific instance of the service.

Back on track, let's get our service rolling.

## The public methods of `ServiceInstance`

Now that we have our instance, hopefully, we should be able to control it:

    if ($svc instanceof ServiceInstace) {
        if ($svc->isRunning()) {
            $svc->stop();
        } else {
            $svc->start();
        }
    }

### From the command line

If all we want is to control the service directly from the command line without
any hassle? Well, there is a way to do that too! The `ServiceController` class
is actually a valid Application, so you can run it like any other application,
and get a fully featured service control application.

    $app = new \Cherry\Cli\ServiceController(
        "local:/services/info.noccylabs.testservice#0", __DIR__);
    App::run($app,__DIR__);

Smallest CLI application ever! And it does just what you would expect:

    $ ./myservicectl status
    Not running.
    $ ./myservicectl start
    Starting ... Done
    $ ./myservicectl status
    Running.
    $ ./myservicectl restart
    Restarting service ... Done
    $ ./myservicectl stop
    Stopping ... Done
    $


