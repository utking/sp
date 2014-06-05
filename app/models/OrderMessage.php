<?php

class OrderMessage extends Phalcon\Mvc\Model
{
    public function initialize() {
        $this->setSource('sp_payment_msg');
    }
    
}
