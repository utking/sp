<?php

class RulesController extends ControllerBase {

    protected function initialize() {
        $this->tag->appendTitle('Правила');
        parent::initialize();
    }

    public function indexAction() {
        $this->view->rules = StaticPages::getRules();
    }

}
