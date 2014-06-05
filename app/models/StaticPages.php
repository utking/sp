<?php

class StaticPages extends Phalcon\Mvc\Model
{
    public function initialize() {
        $this->setSource('sp_static_pages');
    }
    
    public static function getRules() {
        $rules = StaticPages::findFirst(array(
            'conditions' => 'page_alias = ?1',
            'bind' => array(
                1 => 'RULES_PAGE'
            )
        ));
        if ($rules) {
            return $rules->page_text;
        }
        return '';
    }
    
    public static function saveRules($new_text) {
        $rules = StaticPages::findFirst(array(
            'conditions' => 'page_alias = ?1',
            'bind' => array(
                1 => 'RULES_PAGE'
            )
        ));
        if ($rules) {
            $rules->page_text = $new_text;
        } else {
            $rules = new StaticPages();
            $rules->page_alias = 'RULES_PAGE';
            $rules->page_text = $new_text;
        }
        if (!$rules->save()) {
            return false;
        }
        return true;
    }
    
}
