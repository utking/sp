<?php

use Phalcon\Db\RawValue;

class CategoriesController extends ControllerBase {
    
    protected function initialize() {
        $this->tag->prependTitle('Закупки');
        parent::initialize();
    }

    public function indexAction() {
        $this->view->categories = Categories::find(array(
                "conditions" => "parent_category_id = 0"
            ));
    }
    
    public function viewAction() {
        $args = func_get_args();
        if (count($args) > 0) {
            $id = (int)$args[0];
            $this->view->category = Categories::findFirst(array(
                "conditions" => "id = ?1",
                "bind" => array(1 => (int)$id)
            ));
            if (!$this->view->category) {
                $this->flashSession->error('Не существующая закупка');
                return $this->response->redirect('/categories/');
            }
            $this->view->category_child_cats = Categories::find(array(
                "conditions" => "parent_category_id = ?1",
                "bind" => array(1 => (int)$id)
            ));
            $this->view->products = Product::find(array(
                "conditions" => "category_id = ?1",
                "bind" => array(1 => (int)$id)
            ));
            $this->view->forum_msgs = ForumMessage::find(array(
                'conditions' => 'category_id = ?1',
                "bind" => array(1 => (int)$id),
                'order' => 'item_datetime DESC'
            ));
        } else {
            $this->flashSession->error('Закупка не существует');
            return $this->response->redirect('/categories/');
        }
    }
    
    public function new_messageAction() {
        
        $auth = $this->session->get('auth');
        $answer = Recaptcha::check(
                '6LdFpfQSAAAAAPJXXlZLVgl-g-442tIQLkjS9vwx', 
                $_SERVER['REMOTE_ADDR'], 
                $this->request->getPost('recaptcha_challenge_field'), 
                $this->request->getPost('recaptcha_response_field')
        );
        if ($this->request->isPost() && $this->request->hasPost('category_id') && $this->request->hasPost('forum_new_msg_text')) {
            $category_id = $this->request->getPost('category_id', 'int');
            $category = Categories::findFirst($category_id);
            if (!$category) {
                $this->flashSession->error('Не найдена закупка для добавляемого сообщения');
                return $this->response->redirect('/categories/view/' . $category_id);
            }
            $msg_text = $this->request->getPost('forum_new_msg_text', 'string');
            if (trim($msg_text) == '') {
                $this->flashSession->error('Пустое сообщение не может быть добавлено');
                return $this->response->redirect('/categories/view/' . $category_id);
            }
            
			if (!isset($auth['id'])) {
				$this->flashSession->error('Войдите под своими учетными данными чтобы участвовать в обсуждении.');
                return $this->response->redirect('/categories/view/' . $category_id);
			}

            $new_forum_msg = new ForumMessage();
            $new_forum_msg->item_datetime = new RawValue('default');
            $new_forum_msg->user_id = $auth['id'];
            $new_forum_msg->msg = $msg_text;
            $new_forum_msg->category_id = $category_id;
			if (!$answer) {
				$this->flashSession->error('Не верный код с картинки');
				$this->tag->setDefault('forum_new_msg_text', $msg_text);
				return $this->dispatcher->forward(array(
					'action' => 'view',
					'params' => array($category_id)
				));
			}
            if (!$new_forum_msg->save()) {
                $this->flashSession->error('Новое сообщение не добавлено');
                foreach ($new_forum_msg->getMessages() as $message) {
                    $this->flashSession->error($message);
                }
                return $this->response->redirect('/categories/view/' . $category_id);
            } else {
                $this->flashSession->success('Сообщение добавлено');
                return $this->response->redirect('/categories/view/' . $category_id . '#ne_msg');
            }
        }
        $this->flashSession->error('Новое сообщение не добавлено. Неверные параметры');
        return $this->response->redirect('/categories/');
    }
}