<?php

class OrderMessage extends Phalcon\Mvc\Model
{
    public function initialize() {
        $this->setSource('sp_payment_msg');
        $this->hasOne('id', 'User', 'from_user_id');
    }
    
}
