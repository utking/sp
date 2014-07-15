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
class AdminSecurity extends Plugin {

    public function __construct($dependencyInjector) {
        $this->_dependencyInjector = $dependencyInjector;
    }

    public function getAcl() {
        if (true || !isset($this->persistent->acl)) {

            $acl = new Phalcon\Acl\Adapter\Memory();

            $acl->setDefaultAction(Phalcon\Acl::DENY);

            //Register roles
            $roles = array(
                'administrators' => new Phalcon\Acl\Role('Administrators'),
                'guests' => new Phalcon\Acl\Role('Guests')
            );
            foreach ($roles as $role) {
                $acl->addRole($role);
            }
            
            $adminPrivateResources = array(
                'signup' => array('list'),
                'rules' => array('index'),
                'feed' => array('index'),
                'categories' => array('index', 'list', 'view', 'delete', 'add', 'remove_forum_msg',
                    'view_messages', 'send_response', 'remove_msg', 'update_orders',
                    'load100sp', 'fetch100sp', 'save100sp', 'edit', 'save', 'drop_products', 'new_message'),
                'profile' => array('index', 'orders', 'view', 'messages', 'update_messages', 'reply_message', 'change_pass', 'set_pass'),
                'order' => array('view', 'update', 'approve_order'),
                'users' => array('edit', 'index'),
                'product' => array('index', 'list', 'view', 'orders', 'edit', 'add', 'save', 'attrs', 'remove_attr', 
                    'add_attr', 'payments', 'send_messages')
            );
                        
            foreach ($adminPrivateResources as $resource => $actions) {
                $acl->addResource(new Phalcon\Acl\Resource($resource), $actions);
            }

            //Public area resources
            $publicResources = array(
                'index' => array('index'),
                'home' => array('index'),
                'signup' => array('login', 'logout', 'index'),
            );
            foreach ($publicResources as $resource => $actions) {
                $acl->addResource(new Phalcon\Acl\Resource($resource), $actions);
            }

            //Grant access to public areas to both users and guests
            foreach ($roles as $role) {
                //echo $role->getName() . ': <pre>' . print_r($publicResources, 1) . "</pre><br>";
                foreach ($publicResources as $resource => $actions) {
                    $acl->allow($role->getName(), $resource, '*');
                }
            }
            
            foreach ($adminPrivateResources as $resource => $actions) {
                foreach ($actions as $action) {
                    $acl->allow('Administrators', $resource, $action);
                    //$acl->deny('Users', $resource, $action);
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
            $role = 'Administrators';
        }

        $controller = $dispatcher->getControllerName();
        $action = $dispatcher->getActionName();

        $acl = $this->getAcl();
        
        $allowed = $acl->isAllowed($role, $controller, $action);
        
        if ($allowed != Acl::ALLOW) {
            $this->flash->error("Войдите под своим логином и паролем");
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
