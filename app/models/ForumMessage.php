<?php

class ForumMessage extends Phalcon\Mvc\Model
{
    public function initialize() {
        $this->setSource('sp_forum_message');
    }
    
}
