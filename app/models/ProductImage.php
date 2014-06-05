<?php

class ProductImage extends Phalcon\Mvc\Model
{
    public function initialize() {
        $this->setSource('sp_product_image');
    }
    
    public static function getProductImage($product_id) {
        $img = ProductImage::findFirst(array(
            'conditions' => 'product_id = ?1',
            'bind' => array(
                1 => (int)$product_id
            )
        ));
        if ($img) {
            return $img->img_data;
        }
        return '';
    }
    
}
