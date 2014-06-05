<?php

class UserMessage extends Phalcon\Mvc\Model
{
    public function initialize() {
        $this->setSource('sp_messages');
    }
    
}
