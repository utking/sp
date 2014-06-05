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
    
    public function rulesAction() {
        $this->tag->appendTitle('Правила');
        $this->view->rules = StaticPages::getRules();
    }
    
    public function save_rulesAction() {
        if ($this->request->isPost() && $this->request->hasPost('rules_text')) {
            $rules_text = strip_tags(trim($this->request->getPost('rules_text')), '<b><i><p><br><h1><h2><h3><h4><h5><h6>');
            if (!StaticPages::saveRules($rules_text)) {
                $this->flashSession->error('Ошибка обновления правил');
                return $this->response->redirect('/home/edit_rules/');
            }
            $this->flashSession->success('Правила обновлены');
            return $this->response->redirect('/home/rules/');
        }
    }

    public function edit_rulesAction() {
        ;
    }
}
