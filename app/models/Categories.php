<?php

class Categories extends Phalcon\Mvc\Model
{
    public function initialize() {
        $this->setSource('sp_categories');
    }
    
    public static function isStopped($category_id) {
        $category = Categories::findFirst($category_id);
        if ($category) {
            $stop_datetime = new DateTime($category->stop_datetime);
            $now = new DateTime();
            return $now > $stop_datetime;
        }
        return TRUE;
    }
    
    public static function hasChildCategories($category_id) {
        $category = Categories::findFirst(array(
            'conditions' => 'parent_category_id = ?1',
            'bind' => array(
                1 => $category_id
            )
        ));
        return ($category == true);
    }
    
}
