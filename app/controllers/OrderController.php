<?php

class OrderController extends ControllerBase {
    
    protected function initialize() {
        $this->tag->prependTitle('Заказы');
        parent::initialize();
    }

    public function cancelAction() {
        $auth = $this->session->get('auth');
        if (!isset($auth['id'])) {
            return $this->response->redirect('/');
        }
        $user_id = $auth['id'];
        if ($this->request->isPost()) {
            $order_id = $this->request->getPost('uid', 'int');
            $order = Order::findFirst(array(
                "conditions" => "id = ?1 AND user_id = ?2",
                "bind" => array(
                    1 => $order_id,
                    2 => $user_id
                )
            ));
            
            if (!$order) {
                $this->flashSession->error('Ошибка удаления заказа. Заказ не существует');
                return $this->response->redirect('/profile');
            }
            
            if ($order->is_approved) {
                $this->flashSession->error('Ошибка удаления заказа. Нельзя отказаться от подтвержденного заказа');
                return $this->response->redirect('/profile');
            }
            
            $product = Product::findFirst($order->product_id);
            if ($product && Product::isStopped($product->id)) {
                $this->flashSession->error('Ошибка удаления заказа. Прием заказов завершен.');
                return $this->response->redirect('/profile');
            }
            
            $this->db->begin();
            if (!$order->delete()) {
                $this->db->rollback();
                $this->flashSession->error('Ошибка удаления заказа');
                return $this->response->redirect('/profile');
            }
            $this->db->commit();
                
            $this->flashSession->success('Заказ удален');
            return $this->response->redirect('/profile');
        } else {
            return $this->response->redirect('/');
        }
    }
}