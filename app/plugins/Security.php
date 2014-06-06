<?php

use Phalcon\Events\Event,
    Phalcon\Mvc\User\Plugin,
    Phalcon\Mvc\Dispatcher,
    Phalcon\Acl;

/**
 * Security
 *
 * This is the security plugin which controls that users only have access to the modules they're assigned to
 */
class Security extends Plugin {

    public function __construct($dependencyInjector) {
        $this->_dependencyInjector = $dependencyInjector;
    }

    public function getAcl() {
        if (true || !isset($this->persistent->acl)) {

            $acl = new Phalcon\Acl\Adapter\Memory();

            $acl->setDefaultAction(Phalcon\Acl::DENY);

            //Register roles
            $roles = array(
                'guests' => new Phalcon\Acl\Role('Guests'),
                'users' => new Phalcon\Acl\Role('Users')
            );
            foreach ($roles as $role) {
                $acl->addRole($role);
            }

            //Private area resources
            $privateResources = array(
                'order' => array('cancel'),
                'signup' => array('logout'),
                'profile' => array('orders', 'view', 'index', 'messages', 'update_messages', 'reply_message')
            );
                        
            foreach ($privateResources as $resource => $actions) {
                $acl->addResource(new Phalcon\Acl\Resource($resource), $actions);
            }
            
            //Public area resources
            $publicResources = array(
                'index' => array('index'),
                'home' => array('index', 'rules'),
                'rules' => array('index'),
                'feed' => array('index'),
                'signup' => array('login', 'index', 'register'),
                'categories' => array('index', 'list', 'view'),
                'product' => array('index', 'list', 'view')
            );
            foreach ($publicResources as $resource => $actions) {
                $acl->addResource(new Phalcon\Acl\Resource($resource), $actions);
            }

            //Grant access to public areas to both users and guests
            foreach ($roles as $role) {
                foreach ($publicResources as $resource => $actions) {
                    $acl->allow($role->getName(), $resource, '*');
                }
            }

            //Grant acess to private area to role Users
            foreach ($privateResources as $resource => $actions) {
                foreach ($actions as $action) {
                    $acl->allow('Users', $resource, $action);
                    $acl->deny('Guests', $resource, $action);
                }
            }
            
            //The acl is stored in session, APC would be useful here too
            $this->persistent->acl = $acl;
        }

        return $this->persistent->acl;
    }

    /**
     * This action is executed before execute any action in the application
     */
    public function beforeDispatch(Event $event, Dispatcher $dispatcher) {
        //echo $dispatcher->getControllerName() . ' / ' . $dispatcher->getActionName() . '<br>';
        $auth = $this->session->get('auth');
        
        if (!$auth) {
            $role = 'Guests';
        } else {
            $role = 'Users';
        }

        $controller = $dispatcher->getControllerName();
        $action = $dispatcher->getActionName();

        $acl = $this->getAcl();
        
        $allowed = $acl->isAllowed($role, $controller, $action);
        
        if ($allowed != Acl::ALLOW) {
            $this->flash->error("Нет прав доступа к этой странице");
            $dispatcher->forward(
                    array(
                        'controller' => 'home',
                        'action' => 'index'
                    )
            );
            return false;
        }
    }

}
