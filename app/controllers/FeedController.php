<?php

class FeedController extends ControllerBase {

    protected function initialize() {
        $this->tag->appendTitle('Отзывы и хвастики');
        parent::initialize();
    }

    public function indexAction() {
        $this->view->feed = StaticPages::getFeed();
    }

}
