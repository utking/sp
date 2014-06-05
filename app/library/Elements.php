<?php

/**
 * Elements
 *
 * Helps to build UI elements for the application
 */
class Elements extends Phalcon\Mvc\User\Component {

    private $_headerMenu = array(
        'pull-left' => array(
            'categories' => array(
                'caption' => 'Главная',
                'action' => 'index'
            ),
        ),
        'pull-right' => array(
            'signup' => array(
                'caption' => 'Вход',
                'items' => array(
                    array(
                        'caption' => 'Войти',
                        'action' => 'login'
                    ),
                    array(
                        'caption' => 'Регистрация',
                        'action' => 'index'
                    ),
                )
            ),
        )
    );

    /**
     * Builds header menu with left and right items
     *
     * @return string
     */
    public function getMenu() {
        
        //echo '<pre>', print_r($this->_headerMenu, 1), '</pre>';

        $auth = $this->session->get('auth');
        if ($auth) {
            $this->_headerMenu['pull-right'] = array(
                'profile' => array(
                    'caption' => 'Профиль',
                    'action' => 'index'
                ),
                'signup' => array(
                    'caption' => 'Выход',
                    'action' => 'logout'
                ),
            );
        } else {
            //Hide menu itemts for Guests
            unset($this->_headerMenu['pull-left']['signup']);
        }

        $controllerName = $this->view->getControllerName();
        $actionName = $this->view->getActionName();
        
        foreach ($this->_headerMenu as $position => $menu) {
            echo '<ul class="nav navbar-nav ', $position, '">';
            foreach ($menu as $controller => $option) {
                if (isset($option['action'])) {
                    if ($controllerName === $controller && $option['action'] === $actionName) {
                        echo '<li class="active">';
                    } else {
                        echo '<li>';
                    }
                    echo Phalcon\Tag::linkTo('/' . $controller . '/' . $option['action'], $option['caption']);
                    echo '</li>';
                } elseif (isset($option['items'])) {
                    if ($controllerName === $controller) {
                        echo '<li class="dropdown active">';
                    } else {
                        echo '<li class="dropdown">';
                    }
                    echo '<a href="#" class="dropdown-toggle" data-toggle="dropdown">',
                    $option['caption'],
                    '<b class="caret"></b></a>',
                    '<ul class="dropdown-menu">';
                    foreach ($option['items'] as $sub_option) {
                        if (isset($sub_option['action'])) {
                            echo '<li>';
                            echo Phalcon\Tag::linkTo('/' . $controller . '/' . $sub_option['action'], $sub_option['caption']);
                            echo '</li>';
                        }
                    }
                    echo '</ul>';
                    echo '</li>';
                }
            }
            echo '</ul>';
        }
        
    }

    /*public function getTabs() {
        $controllerName = $this->view->getControllerName();
        $actionName = $this->view->getActionName();
        echo '<ul class="nav nav-tabs">';
        foreach ($this->_tabs as $caption => $option) {
            if (isset($option['action'])) {
                if ($option['controller'] == $controllerName && ($option['action'] == $actionName || $option['any'])) {
                    echo '<li class="active">';
                } else {
                    echo '<li>';
                }
                echo Phalcon\Tag::linkTo($option['controller'] . '/' . $option['action'], $caption), '<li>';
            }
        }
        echo '</ul>';
    }*/

}
