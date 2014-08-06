<?php

class SpOrder extends Phalcon\Mvc\Model
{
    public function initialize() {
        $this->setSource('sp_order');
        $this->hasOne('product_id', 'Product', 'id');
        $this->hasOne('product_attr_id', 'ProductAttribute', 'id');
        $this->hasOne('user_id', 'User', 'id');
    }
    
    public function notSave()
    {
        //Obtain the flash service from the DI container
        $flash = $this->getDI()->getFlashSession();

        //Show validation messages
        foreach ($this->getMessages() as $message) {
            $flash->error($message);
        }
    }
    
    public static function getStatus($order_id) {
        $order_status = OrderStatus::findFirst(array(
            'conditions' => 'id = ?1',
            'bind' => array(
                1 => (int)$order_id
            )
        ));
        if ($order_status) {
            return $order_status->status_title;
        }
        return '-';
    }
    
}
