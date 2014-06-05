<?php

use Phalcon\Mvc\View\Engine\Volt;
use Phalcon\Mvc\View;
use Phalcon\Logger\Adapter\File as FileAdapter;

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
                '../app/controllers/',
                '../app/plugins/',
                '../app/library/',
                '../app/models/',
                '../app/library/Fonts/',
            )
    )->register();
    
    //Create a DI
    $di = new Phalcon\DI\FactoryDefault();
    
    $di->set('dispatcher', function() use ($di) {
        $eventsManager = $di->getShared('eventsManager');
        
        $security = new Security($di);
        $eventsManager->attach('dispatch', $security);
        $eventsManager->attach("dispatch:beforeException", function($event, $dispatcher, $exception) {

            //Handle 404 exceptions
            if ($exception instanceof DispatchException) {
                $dispatcher->forward(array(
                    'controller' => 'index',
                    'action' => 'show404'
                ));
                return false;
            }

            //Alternative way, controller or action doesn't exist
            if ($event->getType() == 'beforeException') {
                switch ($exception->getCode()) {
                    case \Phalcon\Dispatcher::EXCEPTION_HANDLER_NOT_FOUND:
                    case \Phalcon\Dispatcher::EXCEPTION_ACTION_NOT_FOUND:
                    case \Phalcon\Dispatcher::EXCEPTION_INVALID_HANDLER:
                        $dispatcher->forward(array(
                            'controller' => 'index',
                            'action' => 'show404'
                        ));
                        return false;
                }
            }
        });


        $dispatcher = new Phalcon\Mvc\Dispatcher();
        $dispatcher->setEventsManager($eventsManager);
        return $dispatcher;
    });
    
    $di->set('url', function() use ($config) {
        $url = new \Phalcon\Mvc\Url();
        $url->setBaseUri($config->application->baseUri);
        return $url;
    });
    
    $di->set('config', function() {
        return new Phalcon\Config\Adapter\Ini('../app/config/config.ini');;
    });

    $di->set('elements', function(){
        return new Elements();
    });
    
    

    //Set the database service
    $di->set('db', function() use ($config) {
        return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
            "host" => $config->database->host,
            "username" => $config->database->username,
            "password" => $config->database->password,
            "dbname" => $config->database->name,
            "options" => array(
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
            )
        ));
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

    //Setting up the view component
    $di->set('view', function() {

        $view = new \Phalcon\Mvc\View();

        $view->setViewsDir('../app/views/');

        $view->registerEngines(array(
            ".phtml" => 'voltService'
        ));

        return $view;
    });
    
    $di->set('elements', function() {
        return new Elements();
    });

    //Handle the request
    $application = new \Phalcon\Mvc\Application();
    $application->setDI($di);
    echo $application->handle()->getContent();
} catch (\Phalcon\Exception $e) {
    echo "PhalconException: ", $e->getMessage();
    $logger = new FileAdapter("../logs/errors.log");
    $logger->log("PhalconException: " . $e->getMessage(), \Phalcon\Logger::ERROR);
}
