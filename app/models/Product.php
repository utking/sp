<?php

class Product extends Phalcon\Mvc\Model
{
    public function initialize() {
        $this->setSource('sp_product');
        //$this->hasMany('id', 'Categories', 'category_id');
    }
    
    public static function getProductTitle($product_id) {
        $product = Product::findFirst(array(
            'conditions' => 'id = ?1',
            'bind' => array(
                1 => (int)$product_id
            )
        ));
        if ($product) {
            return $product->title;
        }
        return '-';
    }
    
    public static function getOrderedSumma($product_id) {
        $sum = SpOrder::sum(array(
            'conditions' => 'product_id = ?1 AND order_status_id = 2',
            'bind' => array(
                1 => (int)$product_id
            ),
            'column' => 'order_summa'
            ));
        if ($sum) {
            return $sum;
        }
        return '-';
    }
    
    public static function isStopped($product_id) {
        $product = Product::findFirst($product_id);
        if ($product) {
            return Categories::isStopped($product->category_id);
        }
        return TRUE;
    }
    
}
