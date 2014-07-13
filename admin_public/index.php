<?php

use Phalcon\Mvc\View\Engine\Volt,
        Phalcon\Mvc\View,
        Phalcon\Mvc\Router;

use Phalcon\Logger\Adapter\File as FileAdapter,
    Phalcon\Events\Manager,
    Phalcon\Logger\Adapter\File;

require_once __DIR__ . '/../app/library/simple_html_dom.php';
require_once __DIR__ . '/../app/library/Config/Lite.php';

date_default_timezone_set('Asia/Vladivostok');

function rus_date($date, $full_date = false) {
    $date = new DateTime($date);
    if ($full_date) {
        return $date->format('d.m.Y H:i:s');
    } 
    return $date->format('d.m.Y');
}

try {
    $config = new \Phalcon\Config\Adapter\Ini('../app/config/config.ini');
    //Register an autoloader
    $loader = new \Phalcon\Loader();
    $loader->registerDirs(
            array(
                '../app/admin_controllers/',
                '../app/plugins/',
                '../app/library/',
                '../app/models/',
                '../app/library/Fonts/',
            )
    )->register();

    //Create a DI
    $di = new Phalcon\DI\FactoryDefault();
    
    $di->set('dispatcher', function() use ($di) {
        //Obtain the standard eventsManager from the DI
        $eventsManager = $di->getShared('eventsManager');
        //Instantiate the Security plugin
        $security = new AdminSecurity($di);
        //Listen for events produced in the dispatcher using the Security plugin
        $eventsManager->attach('dispatch', $security);
        $dispatcher = new Phalcon\Mvc\Dispatcher();
        //Bind the EventsManager to the Dispatcher
        $dispatcher->setEventsManager($eventsManager);
        return $dispatcher;
    });
    
    $di->set('url', function() use ($config) {
        $url = new \Phalcon\Mvc\Url();
        $url->setBaseUri($config->application->baseUri);
        return $url;
    });
    
    $di->set('config', function() {
        return new Phalcon\Config\Adapter\Ini('../app/config/config.ini');
    });
    
    $di->set('modelsManager', function() {
        return new Phalcon\Mvc\Model\Manager();
    });

    $di->set('simple_html_dom', function() {
        return new simple_html_dom();
    });

    //Set the database service
    $di->set('db', function() use ($config) {
        $eventsManager = new Phalcon\Events\Manager();

        $logger = new FileAdapter("../app/logs/debug.log");
        
        $eventsManager->attach('db', function($event, $connection) use ($logger) {
            if ($event->getType() == 'beforeQuery') {
                $logger->log($connection->getSQLStatement(), \Phalcon\Logger::INFO);
            }
        });
        $connection = new \Phalcon\Db\Adapter\Pdo\Mysql(array(
            "host" => $config->database->host,
            "username" => $config->database->username,
            "password" => $config->database->password,
            "dbname" => $config->database->name,
            "options" => array(
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
            )
        ));
        $connection->setEventsManager($eventsManager);

        return $connection;
    });

    $di->set('session', function() {
        $session = new Phalcon\Session\Adapter\Files();
        $session->start();
        return $session;
    });

    $di->set('crypt', function() {
        $crypt = $crypt = new Phalcon\Crypt();
        return $crypt;
    });

    $di->set('flash', function() {
        return new Phalcon\Flash\Direct(array(
            'error' => 'alert alert-danger',
            'success' => 'alert alert-success',
            'notice' => 'alert alert-info',
        ));
    });
    
    $di->set('flashSession', function() {
        return new Phalcon\Flash\Session(array(
            'error' => 'alert alert-danger',
            'success' => 'alert alert-success',
            'notice' => 'alert alert-info',
        ));
    });

    $di->set('voltService', function($view, $di) {

        $volt = new Volt($view, $di);

        $compiler = $volt->getCompiler();

        $compiler->addFilter('rus_date', 'rus_date');

        $volt->setOptions(array(
            "compiledPath" => "../app/cache/",
            "compiledExtension" => ".compiled"
        ));

        return $volt;
    });
    
    $di->set('config_lite', function () {
        $config_lite = new Config_Lite();
        $config_lite->read('../app/config/config.ini');
        return $config_lite;
    });

    //Setting up the view component
    $di->set('view', function() {

        $view = new \Phalcon\Mvc\View();

        $view->setViewsDir('../app/admin_views/');

        $view->registerEngines(array(
            ".phtml" => 'voltService'
        ));

        return $view;
    });

    $di->set('elements', function() {
        return new AdminElements();
    });

    //Handle the request
    $application = new \Phalcon\Mvc\Application();
    $application->setDI($di);
    echo $application->handle()->getContent();
} catch (\Phalcon\Exception $e) {
    echo "PhalconException: ", $e->getMessage();
}
