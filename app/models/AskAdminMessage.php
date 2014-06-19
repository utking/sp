<?php

class AskAdminMessage extends Phalcon\Mvc\Model
{
    public function initialize() {
        $this->setSource('sp_category_msg');
    }
    
}
