<?php

use Phalcon\Db\RawValue;

class ProductController extends ControllerBase {
    
    protected function initialize() {
        $this->tag->prependTitle('Товары');
        parent::initialize();
    }

    public function indexAction() {
        $this->view->products = Product::find();
    }
    
    public function viewAction() {
        $args = func_get_args();
        if (count($args) > 0 && $this->request->isGet()) {
            $id = (int)$args[0];
            $this->view->product = Product::findFirst(array(
                "conditions" => "id = ?1",
                "bind" => array(
                    1 => (int)$id
                )
            ));
            if (!$this->view->product) {
                $this->flashSession->error('Товар не существует');
                return $this->response->redirect('/categories/');
            }
            $this->view->product_attributes = ProductAttribute::find(array(
                'conditions' => 'product_id = ?1',
                'bind' => array(
                    1 => (int)$id
                )
            ));
        } else {
            $this->flashSession->error('Товар не существует');
            return $this->response->redirect('/categories/');
        }
    }
    
    public function msgAction() {
        $args = func_get_args();
        if (count($args) == 1 && $this->request->isGet()) {
            $order_id = (int)$args[0];
            $this->view->order = Order::findFirst((int)$args[0]);
            if ($this->view->order) {
                return;
            }
        }
        $this->flashSession->error('Ошибка при попытке оставить сообщение по заказу');
        return $this->response->redirect('/profile/index');
    }
    
    public function save_msgAction() {
        if ($this->request->isPost()) {
            $order_id = $this->request->getPost('order_id', 'int');
            $msg = trim($this->request->getPost('msg', 'string'));
            $order = Order::findFirst($order_id);
            if (!$order) {
                $this->flashSession->error('Ошибка при попытке оставить сообщение по заказу: неизвестный заказ');
                return $this->response->redirect('/profile/index');
            }
            if ($msg === '') {
                $this->flashSession->error('Ошибка при попытке оставить сообщение по заказу: сообщение не должно быть пустым');
                return $this->response->redirect('/product/msg/' . $order_id);
            }
            
            $order_msg = new OrderMessage();
            $order_msg->client_id = $order->user_id;
            $order_msg->order_id = $order->id;
            $order_msg->admin_id = -1;
            $order_msg->item_datetime = new RawValue('default');
            $order_msg->msg = $msg;
            
            if ($order_msg->save()) {
                $this->flashSession->success('Сообщение по заказу # ' . $order_id . ' отправлено администратору');
                return $this->response->redirect('/profile/index');
            } else {
                $this->flashSession->error('Ошибка при попытке оставить сообщение по заказу: не отправлено');
                return $this->response->redirect('/product/msg/' . $order_id);
            }
        } else {
            $this->flashSession->error('Ошибка при попытке оставить сообщение по заказу');
            return $this->response->redirect('/profile/index');
        }
    }
        
    public function orderAction() {
        $auth = $this->session->get('auth');
        $args = func_get_args();
        if (count($args) == 2 && $this->request->isGet()) {
            $product_id = (int)$args[0];
            $attr_id = (int)$args[1];
            
            if (!isset($auth['id'])) {
                $this->flashSession->error('Войдите под своими учетным данными чтобы совершить заказ.');
                return $this->response->redirect('/product/view/' . $product_id);
            }
            
            $product = Product::findFirst(array(
                "conditions" => "id = ?1",
                "bind" => array(1 => (int)$product_id)
            ));
            $attr = ProductAttribute::findFirst(array(
                "conditions" => "id = ?1 AND product_id = ?2",
                "bind" => array(
                    1 => (int)$attr_id,
                    2 => (int)$product_id)
            ));
            if ($product && $attr) {
                if (Product::isStopped($product->id)) {
                    $this->flashSession->error('Товар больше не доступен для заказа. Прием заказов остановлен');
                    return $this->response->redirect('/product/view/' . $product_id);
                }
                $order = Order::findFirst(array(
                    'conditions' => 'product_id = ?1 AND product_attr_id = ?2 AND user_id = ?3',
                    'bind' => array(
                        1 => $product_id,
                        2 => $attr_id,
                        3 => $auth['id']
                    )
                ));
                if ($order) {
                    $order->order_summa += $product->price;
                    $order->product_count += 1;
                    $order->order_datetime = new RawValue('default');
                } else {
                    $order = new Order();
                    $order->product_id = $product_id;
                    $order->product_attr_id = $attr_id;
                    $order->order_status_id = 1;
                    $order->order_summa = $product->price;
                    $order->user_id = $auth['id'];
                    $order->product_count = 1;
                    $order->order_datetime = new RawValue('default');
                }

                if ($order->save()) {
                    $this->flashSession->success('Заказ принят. Не забудьте внести оплату.');
                    return $this->response->redirect('/product/view/' . $product_id);
                } else {
                    foreach ($new_order->getMessages() as $message) {
                        $this->flashSession->error($message);
                    }
                }
            }
        }
        $this->flashSession->error('Ошибка при совершении заказа');
        return $this->response->redirect('/categories/');
    }
}