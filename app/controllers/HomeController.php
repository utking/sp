<?php

class HomeController extends ControllerBase {

    protected function initialize() {
        parent::initialize();
    }

    public function indexAction() {
        return $this->response->redirect('/categories');
    }

	public function rulesAction() {
		;
	}

}
