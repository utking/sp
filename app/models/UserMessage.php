<?php

class UserMessage extends Phalcon\Mvc\Model
{
    public function initialize() {
        $this->setSource('sp_messages');
        $this->hasOne('from_user_id', 'User', 'id', array(
            'alias' => 'User'
        ));
        $this->hasOne('to_user_id', 'User', 'id', array(
            'alias' => 'Receiver'
        ));
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

	public static function findMessages($category_id, $is_new = false) {
		$cat_ids = Categories::getChildIDs($category_id);
		$cat_ids[] = $category_id;
        $messages = self::getBuilder()
                ->from('UserMessage')
				->inWhere("category_id", $cat_ids)
				->andWhere('to_user_id = 0');
		if ($is_new) {
			$messages->andWhere('is_new = 1');
		}
		return $messages->getQuery()->execute();
	}

    
}
