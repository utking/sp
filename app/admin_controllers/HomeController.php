<?php

class HomeController extends ControllerBase {

    protected function initialize() {
        $this->tag->prependTitle('Главная');
        parent::initialize();
    }

    public function indexAction() {
        $auth = $this->session->get('auth');
        if (!isset($auth['id'])) {
            return $this->dispatcher->forward(array(
                            'controller' => 'signup',
                            'action' => 'login'
                ));
        } else {
            return $this->dispatcher->forward(array(
                            'controller' => 'categories',
                            'action' => 'index'
                ));
        }
    }

}
