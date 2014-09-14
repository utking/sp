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
            $this->view->order = SpOrder::findFirst((int)$args[0]);
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
            $order = SpOrder::findFirst($order_id);
            if (!$order || $order->user_id == 0) {
                $this->flashSession->error('Ошибка при попытке оставить сообщение по заказу: неизвестный заказ');
                return $this->response->redirect('/profile/index');
            }
            if ($msg === '') {
                $this->flashSession->error('Ошибка при попытке оставить сообщение по заказу: сообщение не должно быть пустым');
                return $this->response->redirect('/product/msg/' . $order_id);
            }
            
            $category_id = Product::findFirst($order->product_id)->category_id;
            
            $order_msg = new OrderMessage();
            $order_msg->client_id = $order->user_id;
            $order_msg->category_id = $category_id;
            $order_msg->admin_id = -1;
            $order_msg->item_datetime = new RawValue('default');
            $order_msg->msg = $msg;
            
            if ($order_msg->save()) {
                $this->flashSession->success('Сообщение по заказу # ' . $order_id . ' отправлено администратору');
                
                //send email to admin
                $email = $this->di->get('config')->mail->admin_email;
                $msg_body = 'Сообщение по заказу # ' . $order_id . '" от пользователя ' . User::getLogin($order->user_id) . ':<br>' . 
                        "\r\n<br>$msg.";
                $headers = 'From: ' . $this->di->get('config')->mail->from . "\r\n";
                $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
                
                mail($email, "Вопрос по заказу", $msg_body, $headers);
                //END send email to admin
                
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

    public function save_commentAction() {
        $auth = $this->session->get('auth');
        if (!isset($auth['id'])) {
            $this->flashSession->error('Войдите под своими учетным данными.');
            return $this->response->redirect('/profile/index');
        }
        if ($this->request->isPost()) {
            $order_id = $this->request->getPost('order_id', 'int');
            $msg = trim($this->request->getPost('comment', 'string'));
            $order = SpOrder::findFirst($order_id);
            if (!$order) {
                $this->flashSession->error('Ошибка при попытке оставить комментарий по заказу: неизвестный заказ');
                return $this->response->redirect('/profile/index');
            }
            if (Product::isStopped($order->product_id)) {
                $this->flashSession->error('Ошибка при попытке оставить комментарий по остановленному заказу');
                return $this->response->redirect('/profile/index');
            }
            if ($msg === '') {
				$order->info = new RawValue('default');
			}
			$order->info = $msg;
			if (!$order->save()) {
				$this->flashSession->error('Ошибка добавления комментария по заказу');
				foreach ($order->getMessages() as $message) {
					$this->flashSession->error($message);
				}
				return $this->response->redirect('/product/comment_order/' . $order_id);
			}
            $this->flashSession->success('Комментарий по заказу оставлен');
            return $this->response->redirect('/profile/index');
            
        } else {
            $this->flashSession->error('Ошибка при попытке оставить комментарий по заказу');
            return $this->response->redirect('/profile/index');
        }
    }

	public function comment_orderAction() {
        $args = func_get_args();
        if (count($args) == 1 && $this->request->isGet()) {
            $order_id = (int)$args[0];
            $this->view->order = SpOrder::findFirst((int)$args[0]);
            if ($this->view->order) {
				$this->tag->setDefault('comment', $this->view->order->info);
                return;
            }
        }
        $this->flashSession->error('Ошибка при попытке оставить комментарий по заказу');
        return $this->response->redirect('/profile/index');
	}
        
    public function orderAction() {
        $result = new stdClass();
        $auth = $this->session->get('auth');
        if ($this->request->isPost() && $this->request->hasPost('product_id') && $this->request->hasPost('attr_id')) {
            $product_id = (int)$this->request->getPost('product_id', 'int');
            $attr_id = (int)$this->request->getPost('attr_id', 'int');
            $product_count = (int)$this->request->getPost('product_count', 'int');
            $info = trim((string)$this->request->getPost('info', 'string'));
            
            if (!isset($auth['id'])) {
                $result->hasError = true;
                $result->errorMsg = "Войдите под своими учетным данными чтобы совершить заказ";
                die(json_encode($result));
            }

			if ($info == '') {
                $result->hasError = true;
                $result->errorMsg = "Не указаны коментарии к заказу!";
                die(json_encode($result));
			}
            
            if ($product_count < 1) {
                $result->hasError = true;
                $result->errorMsg = "Не верное количество товара: " . $product_count;
                die(json_encode($result));
            }
            
            $product = Product::findFirst(array(
                "conditions" => "id = ?1",
                "bind" => array(1 => $product_id)
            ));
            $attr = ProductAttribute::findFirst(array(
                "conditions" => "id = ?1 AND product_id = ?2",
                "bind" => array(
                    1 => $attr_id,
                    2 => $product_id)
            ));
            
            if (!$product) {
                $result->hasError = true;
                $result->errorMsg = "Товар не найден";
                die(json_encode($result));
            }
            
            if (!$attr) {
                $result->hasError = true;
                $result->errorMsg = "Размер не найден";
                die(json_encode($result));
            }
            
            if ($product && $attr) {
                if (Product::isStopped($product->id)) {
                    $result->hasError = true;
                    $result->errorMsg = "Товар больше не доступен для заказа. Прием заказов остановлен";
                    die(json_encode($result));
                }
                $order = SpOrder::findFirst(array(
                    'conditions' => 'product_id = ?1 AND product_attr_id = ?2 AND user_id = ?3',
                    'bind' => array(
                        1 => $product_id,
                        2 => $attr_id,
                        3 => $auth['id']
                    )
                ));
                
                $this->db->begin();
                for ($i = 0; $i < $product_count; $i++) {
                    $order = new SpOrder();
                    $order->is_approved = false;
                    $order->product_id = $product_id;
                    $order->product_attr_id = $attr_id;
                    $order->order_status_id = 1;
                    if ($info === '') {
                        $order->info = new RawValue('default');
                    } else {
                        $order->info = $info;
                    }
                    $order->order_summa = $product->price;
                    $order->user_id = $auth['id'];
                    $order->product_count = 1;
                    $order->is_paid = false;
                    $order->order_datetime = new RawValue('NOW()');

                    if (!$order->save()) {
                        $result->hasError = true;
                        $result->errorMsg = "Ошибка при совершении заказа";
						foreach ($order->getMessages() as $message) {
							$result->errorMsg .= $message . "<br>";
						}
                        $this->db->rollBack();
                        die(json_encode($result));
                    }
                }
                $this->db->commit();
            }
            $result->hasError = false;
            die(json_encode($result));
        } else {
            $result->hasError = true;
            $result->errorMsg = "Не верные параметры: " . print_r($_POST,1);
            die(json_encode($result));
        }
    }
}
