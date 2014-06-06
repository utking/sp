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
    
    public function feedAction() {
        $this->tag->appendTitle('Отзывы и хвастики');
        $this->view->feed = StaticPages::getFeed();
    }
    
    public function save_rulesAction() {
        if ($this->request->isPost() && $this->request->hasPost('rules_text')) {
            $rules_text = trim($this->request->getPost('rules_text'));
            if (!StaticPages::saveRules($rules_text)) {
                $this->flashSession->error('Ошибка обновления правил');
                return $this->response->redirect('/home/edit_rules/');
            }
            $this->flashSession->success('Правила обновлены');
            return $this->response->redirect('/rules/');
        }
    }

    public function save_feedAction() {
        if ($this->request->isPost() && $this->request->hasPost('feed_text')) {
            $feed_text = trim($this->request->getPost('feed_text'));
            if (!StaticPages::saveFeed($feed_text)) {
                $this->flashSession->error('Ошибка обновления правил');
                return $this->response->redirect('/home/edit_feed/');
            }
            $this->flashSession->success('Отзывы и хвастики обновлены');
            return $this->response->redirect('/feed/');
        }
    }

    public function edit_rulesAction() {
        $auth = $this->session->get('auth');
        if (!isset($auth['id'])) {
            return $this->dispatcher->forward(array(
                            'controller' => 'signup',
                            'action' => 'login'
                ));
        }
        $this->tag->appendTitle('Изменить правила');
        $this->view->rules = StaticPages::getRules();
        $this->tag->setDefault('rules_text', $this->view->rules);
    }
    public function edit_feedAction() {
        $auth = $this->session->get('auth');
        if (!isset($auth['id'])) {
            return $this->dispatcher->forward(array(
                            'controller' => 'signup',
                            'action' => 'login'
                ));
        }
        $this->tag->appendTitle('Изменить');
        $this->view->feed = StaticPages::getFeed();
        $this->tag->setDefault('feed_text', $this->view->feed);
    }

}
