<?php

class FeedController extends ControllerBase {

    protected function initialize() {
        $this->tag->appendTitle('Отзывы и хвастики');
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
			$this->view->feed = StaticPages::getFeed();
        }
    }
    
}
