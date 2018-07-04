<?php

use Phalcon\Loader;
use Phalcon\Mvc\View;
use Phalcon\Mvc\Application;
use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\Url as UrlProvider;
use Phalcon\Db\Adapter\Pdo\Mysql as DbAdapter;
use Phalcon\Session\Adapter\Files as Session;
use Phalcon\Flash\Direct as FlashDirect;
use Phalcon\Flash\Session as FlashSession;



// Define some absolute path constants to aid in locating resources
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

// Register an autoloader
$loader = new Loader();

$loader->registerDirs(
    [
        APP_PATH . '/controllers/',
        APP_PATH . '/models/',
    ]
);

$loader->register();

// Create a DI
$di = new FactoryDefault();

// Setup the view component
$di->set('view', function () {
    $view = new View();
    $view->setViewsDir(APP_PATH . '/views/');
    return $view;
});

// Setup a base URI
$di->set('url', function () {
    $url = new UrlProvider();
    $url->setBaseUri('//localhost/myapp/');
    return $url;
});

// Setup the database service
$di->set('db', function () {
        return new DbAdapter(
            [
                'host'     => 'localhost',
                'username' => 'root',
                'password' => '',
                'dbname'   => 'myapp_db',
            ]
        );
    }
);

// Start the session the first time when some component request the session service
$di->setShared(
    'session',
    function () {
        $session = new Session();

        $session->start();

        return $session;
    }
);

// Set up the flash service
$di->set(
    'flash',
    function () {
        return new FlashDirect();
    }
);

// Set up the flash session service
$di->set(
    'flashSession',
    function () {
        return new FlashSession();
    }
);

$application = new Application($di);

try {
    // Handle the request
    $response = $application->handle();
    $response->send();
} catch (\Exception $e) {
    echo 'Exception: ', $e->getMessage();
}
