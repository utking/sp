<?php

class Categories extends Phalcon\Mvc\Model
{
    public function initialize() {
        $this->setSource('sp_categories');
        $this->hasMany('id', 'Product', 'category_id');
    }
    
    protected static function di()
    {
        return \Phalcon\DI\FactoryDefault::getDefault();
    }
    
    protected static function getBuilder()
    {
        $di      = self::di();
        $manager = $di['modelsManager'];
        $builder = $manager->createBuilder();
        $builder->from(get_called_class());

        return $builder;
    }
    
    public static function productsCount($category_id) {
        $counter = 0;
        $subcategories = Categories::find(array(
            'conditions' => 'parent_category_id = ?1 AND hidden = 0',
            'bind' => array(
                1 => $category_id
            )
        ));
        if ($subcategories && count($subcategories)) {
            foreach ($subcategories as $cur_child) {
                $counter += Categories::productsCount($cur_child->id);                
            }
            return $counter;
        }
        
        $category = Categories::findFirst($category_id);
        if ($category && $category->hidden) {
            return 0;
        }
        $products = Product::find(array(
            'conditions' => 'category_id = ?1',
            'bind' => array(
                1 => $category_id
            )
        ));
        return count($products);
    }

    public static function getOrderCount($category_id) {
        $counter = 0;
        $subcategories = Categories::find(array(
            'conditions' => 'parent_category_id = ?1 AND hidden = 0',
            'bind' => array(
                1 => $category_id
            )
        ));
        $orders = self::getBuilder()
                ->from(array('SpOrder', 'Product'))
                ->where("SpOrder.product_id = Product.id and Product.category_id = $category_id") //, array('category_id' => $category_id))
                ->andWhere('order_status_id != 3')
                ->getQuery()
                ->execute();
        $counter += count($orders);
        if ($subcategories && count($subcategories)) {
            foreach ($subcategories as $cur_child) {
                $counter += Categories::getOrderCount($cur_child->id);                
            }
            return $counter;
        }
        return $counter;
    }

    public static function isStopped($category_id) {
        $parent_cat_id = Categories::getRootCategoryID($category_id);
        $category = Categories::findFirst($parent_cat_id);
        if ($category) {
            $stop_datetime = new DateTime($category->stop_datetime);
            $now = new DateTime();
            return $now > $stop_datetime;
        }
        return TRUE;
    }
    
    public static function getOrderMessages($product_id) {
        $product = Product::findFirst($product_id);
        
        return OrderMessage::find(array(
            'conditions' => 'category_id = ?1',
            'bind' => array(
                1 => $product->category_id
            ),
            'order' => 'item_datetime DESC'
        ));
    }
    
    public static function getOrderMessagesByCategory($category_id) {
        return OrderMessage::find(array(
            'conditions' => 'category_id = ?1',
            'bind' => array(
                1 => $category_id
            ),
            'order' => 'item_datetime DESC'
        ));
    }
    
    public static function getTitle($category_id) {
        $category = Categories::findFirst(array(
            'conditions' => 'id = ?1',
            'bind' => array(
                1 => (int)$category_id
            )
        ));
        if ($category) {
            return $category->title;
        }
        return '-';
    }
    
    public static function getRootCategoryID($category_id) {
        while (Categories::getParentCategoryId($category_id)) {
            $category_id = Categories::getParentCategoryId($category_id);
        }
        return $category_id;
    }
        
    private static function getParentCategoryId($category_id) {
        $category = Categories::findFirst(array(
            'conditions' => 'id = ?1',
            'bind' => array(
                1 => (int)$category_id
            )
        ));
        if ($category && !is_null($category->parent_category_id)) {
            return $category->parent_category_id;
        }
        return false;
    }
    
    public static function hasChildCategories($category_id) {
        $category = Categories::find(array(
            'conditions' => 'parent_category_id = ?1',
            'bind' => array(
                1 => $category_id
            )
        ));
        return (count($category));
    }
    
}
