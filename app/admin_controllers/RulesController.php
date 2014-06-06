<?php

class RulesController extends ControllerBase {

    protected function initialize() {
        $this->tag->appendTitle('Правила');
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
			$this->view->rules = StaticPages::getRules();
        }
    }
    
}
