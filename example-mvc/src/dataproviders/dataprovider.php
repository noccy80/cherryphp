<?php

namespace CherryTree\DataProviders;

/**
 * @brief DataProvider to help serve up and manage content.
 * This is basically a model on steroids.
 *
 */
abstract class Component {

    const CAN_SERVE = 'can-serve';
    const CAN_BROWSE = 'can-browse';

    protected $namespace = null;
    protected $contenttypes = [];

    public static function getProvider($uri) {
        static $res = [];
        if (!array_has_key($resource,$res)) {

        }
    }

    /**
     * @brief Retrieve an object from a model.
     *
     * The URI should be something like:
     *  - gallery:public
     *  - gallery:public/photos/1332
     *
     * @param string $uri The object URI to fetch
     */
    public static function getObject($uri) {


    }

    public function __construct($resourcekey=null) {

    }

}

class Repository {
    private $modules = [];
    function __construct() {

    }
    public static function register(Component $class) {
        var_dump($class->__meta());
    }
}



class Gallery extends Component implements DataServer, DataBrowser, PanelTab {
    public function __meta() {
        return [
            'namespace' => 'gallery',
            'modulename' => 'data:gallery',
            'name' => 'Gallery Data Provider',
            'version' => '1.0.0',
            'capabilities' => [
                Component::CAN_SERVE,
                Component::CAN_BROWSE
            ],
            'depends' => [
                'Cherry\Db\Database'
            ],
            'provides' => [
                'image/*'
            ]
        ];
    }
    function panelGetIcon() {
        return '/images/panels/gallery.png';
    }
    function panelGetLabel() {
        return 'Gallery';
    }
    function panelGetContents() {
        
    }
}

$r = new Repository();
$r->register(new Gallery());
//App::repository()->register('data:provider.gallery',new Gallery());
