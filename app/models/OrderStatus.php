<?php

class OrderStatus extends Phalcon\Mvc\Model
{
    public function initialize() {
        $this->setSource('sp_order_status');
    }
    
}
