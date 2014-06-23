<?php

use Phalcon\Db\RawValue;

class OrderController extends ControllerBase {
    
    protected function initialize() {
        $this->tag->prependTitle('Заказы');
        parent::initialize();
    }

    public function viewAction() {
        $args = func_get_args();
        $this->view->order_statuses = OrderStatus::find();
        if (count($args) > 0 && $this->request->isGet()) {
            $id = (int)$args[0];
            $this->view->order = Order::findFirst(array(
                "conditions" => "id = ?1",
                "bind" => array(
                    1 => (int)$id
                )
            ));
            $product = Product::findFirst($this->view->order->product_id);
            $this->view->order_messages = OrderMessage::find(array(
                "conditions" => "category_id = ?1",
                "bind" => array(
                    1 => $product->category_id
                )
            ));
            $this->view->category_id = $product->category_id;
        } else {
            $this->view->order = new Order();
        }
    }
    
    public function updateAction() {
        $auth = $this->session->get('auth');
        if ($this->request->isPost() && $this->request->hasPost('order_id')) {
            $order_id = $this->request->getPost('order_id');
            $order = Order::findFirst($order_id);
            if (!$order) {
                $this->flashSession->error('Не найден заказ с таким номером');
                return $this->response->redirect('/order/view/' . $order_id);
            }
            
            if ($this->request->hasPost('send_message')) {
                if (!$this->request->hasPost('msg_box')) {
                    $this->flashSession->error('Пустое сообщение. Не отправлено.');
                    return $this->response->redirect('/order/view/' . $order_id);
                }
                $user_msg = new UserMessage();
                $user_msg->item_datetime = new RawValue('default');
                $user_msg->from_user_id = $auth['id'];
                $user_msg->msg_subject = 'Заказ № ' . $order_id;
                $user_msg->to_user_id = $order->user_id;
                $user_msg->is_new = true;
                $msg_text = trim($this->request->getPost('msg_box', 'string'));
                if ($msg_text == '') {
                    $this->flashSession->error('Пустое сообщение. Не отправлено.');
                    return $this->response->redirect('/order/view/' . $order_id);
                }                
                $user_msg->msg = $msg_text;
                if (!$user_msg->save()) {
                    foreach ($user_msg->getMessages() as $message) {
                        $this->flashSession->error($message);
                    }
                    $this->flashSession->error('Ошибка отправки сообщения пользователю');
                    return $this->response->redirect('/order/view/' . $order_id);
                }
                $this->flashSession->success('Сообщение отправлено');
                return $this->response->redirect('/order/view/' . $order_id);
            } elseif ($this->request->hasPost('save_order')) {
                $order_status_id = (int)$this->request->getPost('order_status', 'int');
                $order_status = OrderStatus::findFirst($order_status_id);
                if (!$order_status) {
                    $this->flashSession->success('Неизвестный статус заказа');
                    return $this->response->redirect('/order/view/' . $order_id);
                }
                $order->order_status_id = $order_status_id;
                if ($order->update()) {
                    $this->flashSession->success('Заказ обновлен');
                    return $this->response->redirect('/order/view/' . $order_id);
                }
                foreach ($order->getMessages() as $message) {
                    $this->flashSession->error($message);
                }
                $this->flashSession->error('Ошибка при попытке внести изменения по заказу');
                return $this->response->redirect('/order/view/' . $order_id);
            } elseif ($this->request->hasPost('remove_order')) {
                $this->db->begin();
                if (!$order->delete()) {
                    $this->db->rollback();
                    $this->flashSession->error('Ошибка удаления заказа');
                    return $this->response->redirect('/order/view/' . $order_id);
                }
                
                $this->db->commit();
                $this->flashSession->success('Заказ удален');
                return $this->response->redirect('/product/orders');
            }
        } else {
            $this->flashSession->error('Ошибка при попытке внести изменения по заказу');
            return $this->response->redirect('/product/orders');
        }
    }
    
    public function approve_orderAction() {
        $result = new stdClass();
        $auth = $this->session->get('auth');
        if ($this->request->isPost() && ($this->request->hasPost('approve_order') || $this->request->hasPost('disapprove_order')) && 
                $this->request->hasPost('order_id')) {
            $order_id = $this->request->getPost('order_id', 'int');
            
            $order = Order::findFirst($order_id);
                        
            if (!$order) {
                $result->hasError = true;
                $result->errorMsg = "Заказ не существует";
                die(json_encode($result));
            }
            
            if (!isset($auth['id'])) {
                $result->hasError = true;
                $result->errorMsg = "Войдите под своими учетными данными";
                die(json_encode($result));
            }
            
            if ($this->request->hasPost('approve_order')) {
                $order->is_approved = true;
            } else {
                $order->is_approved = false;
            }
            
            if (!$order->save()) {
                $result->hasError = true;
                $result->errorMsg = "Подтверждение не выполнено" . PHP_EOL;
                foreach ($order->getMessages() as $errMessage) {
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
    
}