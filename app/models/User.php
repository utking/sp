<?php

class User extends Phalcon\Mvc\Model
{
    
    public function initialize() {
        $this->hasMany('id', 'OrderMessage', 'from_user_id');
    }
    
    public static function getLogin($user_id) {
        if ($user_id <= 0) {
            return 'lilas';
        }
        $user = User::findFirst($user_id);
        if ($user) {
            return $user->login;
        }
        return '###';
    }
    
    public static function generatePassword($length = 8) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $count = mb_strlen($chars);

        for ($i = 0, $result = ''; $i < $length; $i++) {
            $index = rand(0, $count - 1);
            $result .= mb_substr($chars, $index, 1);
        }

        return $result;
    }
    
}
