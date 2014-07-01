<?php

class OrderMessage extends Phalcon\Mvc\Model
{
    public function initialize() {
        $this->setSource('sp_payment_msg');
        $this->hasOne('client_id', 'User', 'id');
        $this->hasOne('category_id', 'Categories', 'id');
    }
    
}
