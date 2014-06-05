<?php

class User extends Phalcon\Mvc\Model
{
    public static function getLogin($user_id) {
        if ($user_id <= 0) {
            return 'Admin';
        }
        $user = User::findFirst($user_id);
        if ($user) {
            return $user->login;
        }
        return '###';
    }
    
}
