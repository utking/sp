<?php

class UserGroup extends Phalcon\Mvc\Model
{
    public function initialize() {
        $this->setSource('user_group');
    }
}
