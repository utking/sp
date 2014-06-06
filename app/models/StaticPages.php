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
    
    public static function getFeed() {
        $feed = StaticPages::findFirst(array(
            'conditions' => 'page_alias = ?1',
            'bind' => array(
                1 => 'FEED_PAGE'
            )
        ));
        if ($feed) {
            return $feed->page_text;
        }
        return '';
    }
    
    public static function saveFeed($new_text) {
        $feed = StaticPages::findFirst(array(
            'conditions' => 'page_alias = ?1',
            'bind' => array(
                1 => 'FEED_PAGE'
            )
        ));
        if ($feed) {
            $feed->page_text = $new_text;
        } else {
            $feed = new StaticPages();
            $feed->page_alias = 'FEED_PAGE';
            $feed->page_text = $new_text;
        }
        if (!$feed->save()) {
            return false;
        }
        return true;
    }
    
}
