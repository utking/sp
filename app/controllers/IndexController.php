<?php

class IndexController extends ControllerBase {
    
    public function indexAction () {
		return $this->response->redirect('/categories/');
    }
    
    public function show404Action() {
        ;
    }
}
