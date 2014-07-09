<?php

class UserMessage extends Phalcon\Mvc\Model
{
    public function initialize() {
        $this->setSource('sp_messages');
        $this->hasOne('from_user_id', 'User', 'id', array(
            'alias' => 'User'
        ));
        $this->hasOne('to_user_id', 'User', 'id', array(
            'alias' => 'Receiver'
        ));
    }
    
}
