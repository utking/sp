<?php

class ProductAttribute extends Phalcon\Mvc\Model
{
    public function initialize() {
        $this->setSource('sp_product_attribute');
    }
    
    public static function getAttributeTitle($attr_id) {
        $attribute = ProductAttribute::findFirst(array(
            'conditions' => 'id = ?1',
            'bind' => array(
                1 => (int)$attr_id
            )
        ));
        if ($attribute) {
            return $attribute->attr;
        }
        return '-';
    }
    
}
