<?php

use Phalcon\Db\RawValue;

class CategoriesController extends ControllerBase {
    
    protected function initialize() {
        $this->tag->prependTitle('Закупки');
        parent::initialize();
    }

    public function indexAction() {
        $this->view->categories = Categories::find(array(
                "conditions" => "parent_category_id = 0 AND hidden = 0"
            ));
    }
    
    public function msgAction() {
        $args = func_get_args();
        if (count($args) == 1 && $this->request->isGet()) {
            $category_id = (int)$args[0];
            $this->view->category = Categories::findFirst((int)$args[0]);
            if ($this->view->category) {
                return;
            }
        }
        $this->flashSession->error('Ошибка при попытке оставить сообщение по закупке');
        return $this->response->redirect('/profile/index');
    }
    
    public function save_msgAction() {
        if ($this->request->isPost()) {
            $category_id = $this->request->getPost('category_id', 'int');
            $msg = trim($this->request->getPost('msg', 'string'));
            $category = Categories::findFirst($category_id);
            if (!$category) {
                $this->flashSession->error('Ошибка при попытке оставить сообщение по заказам в закупке: неизвестная закупка');
                return $this->response->redirect('/profile/index');
            }
            if ($msg === '') {
                $this->flashSession->error('Ошибка при попытке оставить сообщение по заказам в закупке: сообщение не должно быть пустым');
                return $this->response->redirect('/categories/msg/' . $category_id);
            }
            
            $item_msg = new OrderMessage();
            $auth = $this->session->get('auth');
            $item_msg->client_id = (int)$auth['id'];
            $item_msg->category_id = $category_id;
            $item_msg->admin_id = -1;
            $item_msg->item_datetime = new RawValue('default');
            $item_msg->msg = $msg;
            
            if ($item_msg->save()) {
                $this->flashSession->success('Сообщение по заказам в закупке "' . $category->title . '" отправлено администратору');
                return $this->response->redirect('/profile/index');
            } else {
                $this->flashSession->error('Ошибка при попытке оставить сообщение по заказам в закупке: не отправлено');
                return $this->response->redirect('/product/msg/' . $order_id);
            }
        } else {
            $this->flashSession->error('Ошибка при попытке оставить сообщение по заказам в закупке');
            return $this->response->redirect('/profile/index');
        }
    }
    
    public function viewAction() {
        $args = func_get_args();
        if (count($args) > 0) {
            $id = (int)$args[0];
            $this->view->category = Categories::findFirst(array(
                "conditions" => "id = ?1 and hidden = ?2",
                "bind" => array(
                    1 => (int)$id,
                    2 => false
                    )
            ));
            if (!$this->view->category) {
                $this->flashSession->error('Не существующая закупка');
                return $this->response->redirect('/categories/');
            }
            $this->view->category_child_cats = Categories::find(array(
                "conditions" => "parent_category_id = ?1 and hidden = 0",
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
            $this->flashSession->error('Закупка не существует или более не доступна');
            return $this->response->redirect('/categories/');
        }
    }
    
    public function ask_adminAction() {
        $result = new stdClass();
        $auth = $this->session->get('auth');
        if ($this->request->isPost() && $this->request->hasPost('ask_admin') && $this->request->hasPost('id') && $this->request->hasPost('question')) {
            $category_id = $this->request->getPost('id', 'int');
            $category = Categories::findFirst($category_id);
            $msg = trim($this->request->getPost('question', 'string'));
            
            if ($msg === '') {
                $result->hasError = true;
                $result->errorMsg = "Пустое сообщение";
                die(json_encode($result));
            }
            
            if (!$category) {
                $result->hasError = true;
                $result->errorMsg = "Категория не существует";
                die(json_encode($result));
            }
            
            if (!isset($auth['id'])) {
                $result->hasError = true;
                $result->errorMsg = "Войдите под своими учетными данными";
                die(json_encode($result));
            }
            
            $ask_admin = new UserMessage();
            $ask_admin->from_user_id = $auth['id'];
            $ask_admin->msg = $msg;
            $ask_admin->item_datetime = new RawValue('default');
            $ask_admin->to_user_id = 0;
            $ask_admin->is_new = true;
            $ask_admin->category_id = $category_id;
            $ask_admin->msg_subject = 'Вопрос по категории "' . $category->title . '"';
            
            if (!$ask_admin->save()) {
                $result->hasError = true;
                $result->errorMsg = "Сообщение не отправлено: " . PHP_EOL;
                foreach ($ask_admin->getMessages() as $errMessage) {
                    $result->errorMsg += $errMessage . PHP_EOL;
                }
                die(json_encode($result));
            }
            $result->hasError = false;
            die(json_encode($result));
        }
        $result->hasError = true;
        $result->errorMsg = "Не верные параметры";
        die(json_encode($result));
    }
    
    public function new_messageAction() {
        
        $auth = $this->session->get('auth');
        /*$answer = Recaptcha::check(
                '6LdFpfQSAAAAAPJXXlZLVgl-g-442tIQLkjS9vwx', 
                $_SERVER['REMOTE_ADDR'], 
                $this->request->getPost('recaptcha_challenge_field'), 
                $this->request->getPost('recaptcha_response_field')
        );*/
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
            /*if (!$answer) {
                $this->flashSession->error('Не верный код с картинки');
                $this->tag->setDefault('forum_new_msg_text', $msg_text);
                return $this->dispatcher->forward(array(
                            'action' => 'view',
                            'params' => array($category_id)
                ));
            }*/
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
