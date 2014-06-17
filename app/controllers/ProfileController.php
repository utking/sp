<?php

use Phalcon\Db\RawValue;

class ProfileController extends ControllerBase {
    
    protected function initialize() {
        $this->tag->prependTitle('Данные пользователя');
        parent::initialize();
    }

    public function indexAction() {
        $auth = $this->session->get('auth');
        if (isset($auth['id'])) {
            if ($auth['id'] == 0) {
                $this->view->user = new User();
                $this->view->user->login = $this->di->get('config')->staticAdmin->login;
                $this->view->user->email = $this->di->get('config')->staticAdmin->login . '@localhost';
                $this->view->user->full_name = $this->di->get('config')->staticAdmin->login;
                $this->view->user->phone = $this->di->get('config')->staticAdmin->login;
            } else {
                $this->view->user = User::findFirst(array(
                    'conditions' => 'id = ?1',
                    'bind' => array(
                        1 => $auth['id']
                    )
                ));
            }
            $orders = Order::find(array(
                'conditions' => 'user_id = ?1',
                'bind' => array(
                    1 => $auth['id']
                ),
                'order' => 'order_datetime DESC'
            ));
            $categories = array();
            foreach ($orders as $order) {
                $product = Product::findFirst($order->product_id);
                if (!isset($categories[$product->category_id])) {
                    $categories[$product->category_id] = [];
                    $categories[$product->category_id]['order_summa'] = 0;
                }
                $categories[$product->category_id]['orders'][] = $order->id;
                $categories[$product->category_id]['order_summa'] += $order->order_summa;
            }
            $this->view->categories = $categories;

            $this->view->messages = UserMessage::find(array(
                'conditions' => 'to_user_id = ?1 AND is_new = 1',
                'bind' => array(
                    1 => $auth['id']
                )
            ));
        } else {
            $this->view->user = new User();
            $this->view->user->login = '###';
            $this->view->user->full_name = '###';
            $this->view->user->email = '###';
            $this->view->user->phone = '###';
            //$this->view->orders = array();
            $this->view->categories = array();
        }
    }
    
    public function messagesAction() {
        $args = func_get_args();
        $auth = $this->session->get('auth');
        if (count($args) < 1) {
            $this->view->messages = UserMessage::find(array(
                'conditions' => 'to_user_id = ?1',
                'bind' => array(
                    1 => $auth['id']
                ),
                'order' => 'item_datetime DESC'
            ));
        } else { //show unread
            $this->view->messages = UserMessage::find(array(
                'conditions' => 'to_user_id = ?1 AND is_new = ?2',
                'bind' => array(
                    1 => $auth['id'],
                    2 => (int)$args[0]
                ),
                'order' => 'item_datetime DESC'
            ));
        }
    }
    
    public function update_messagesAction() {
        $auth = $this->session->get('auth');
        if ($this->request->isPost() && $this->request->hasPost('unread_messages')) {
            return $this->response->redirect('/profile/messages/1');
        } elseif ($this->request->isPost() && $this->request->hasPost('all_messages')) {
            return $this->response->redirect('/profile/messages/');
        } elseif ($this->request->isPost() && $this->request->hasPost('message_ids')) {
            if ($this->request->hasPost('remove_messages') || $this->request->hasPost('mark_messages_read')) {
                $msg_ids = $this->request->getPost('message_ids', 'int');
                if ($msg_ids) {
                    foreach ($msg_ids as $msg_id) {
                        $cur_msg = UserMessage::findFirst(array(
                            'conditions' => 'id = ?1 AND to_user_id = ?2',
                            'bind' => array(
                                1 => $msg_id,
                                2 => (int)$auth['id']
                            )
                        ));
                        if ($this->request->hasPost('remove_messages')) {
                            if (!$cur_msg->delete()){
                                foreach ($cur_msg->getMessages() as $message) {
                                    $this->flashSession->error($message);
                                }
                            }
                        } else {
                            $cur_msg->is_new = false;
                            if (!$cur_msg->update()){
                                foreach ($cur_msg->getMessages() as $message) {
                                    $this->flashSession->error($message);
                                }
                            }
                        }
                    }
                    return $this->response->redirect('/profile/messages/');
                }
            } else {
                $this->flashSession->error('Не верная операция над сообщением.');
            }
        }
        if (!$this->request->hasPost('message_ids')) {
            $this->flashSession->error('Не выбраны сообщения.');
        }
        return $this->response->redirect('/profile/messages/');
    }
    
    public function reply_messageAction() {
        $auth = $this->session->get('auth');
        $args = func_get_args();
        if ($this->request->isGet() && count($args)) {
            $msg_id = (int)$args[0];
            $msg = UserMessage::findFirst(array(
                'conditions' => 'id = ?1 AND to_user_id = ?2',
                'bind' => array(
                    1 => $msg_id,
                    2 => $auth['id']
                )
            ));
            if ($msg) {
                $this->tag->setDefault('msg_subject', 'Re: ' . $msg->msg_subject);
                $this->tag->setDefault('msg_text', "\n\n > " . join("\n> ", str_split($msg->msg, 80)));
                $this->tag->setDefault('msg_to_user_id', $msg->from_user_id);
            }
            return;
        } elseif ($this->request->isPost() && $this->request->hasPost('msg_to_user_id') && 
                $this->request->hasPost('msg_subject') && $this->request->hasPost('msg_text')) {
            $msg_subject = trim($this->request->getPost('msg_subject', 'string'));
            $msg_text = trim($this->request->getPost('msg_text', 'string'));
            $msg_to_user_id = $this->request->getPost('msg_to_user_id', 'int');
            
            $new_msg = new UserMessage();
            $new_msg->msg = $msg_text;
            $new_msg->item_datetime = new RawValue('default');
            $new_msg->msg_subject = $msg_subject;
            $new_msg->from_user_id = $auth['id'];
            $new_msg->to_user_id = $msg_to_user_id;
            $new_msg->is_new = 1;
            
            if (!$new_msg->save()) {
                foreach ($new_msg->getMessages() as $message) {
                    $this->flashSession->error($message);
                }
            } else {
                $this->flashSession->success('Ответ отправлен');
            }
            
            return $this->response->redirect('/profile/messages/');
        }
        $this->flashSession->error('Не верные данные.');
        return $this->response->redirect('/profile/messages/');
    }
}
