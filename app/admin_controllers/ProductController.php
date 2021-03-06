<?php

use Phalcon\Db\RawValue,
	Phalcon\Mvc\Model\Resultset\Simple;

class ProductController extends ControllerBase {
    
    protected function initialize() {
        $this->tag->prependTitle('Товары');
        parent::initialize();
    }

    public function indexAction() {
        return $this->response->redirect('/caregories/');
    }
    
    public function ordersAction() {
        $this->tag->appendTitle(' - Заказы');
        $orders = SpOrder::find(array(
                    'order' => 'order_datetime'
        ));
        $categories = array();
        foreach ($orders as $order) {
            $product = Product::findFirst($order->product_id);
			if (!$product) { continue; }
            $parent_cat_id = Categories::getRootCategoryID($product->category_id);
            if (!isset($categories[$parent_cat_id])) {
                $categories[$parent_cat_id] = [];
                $categories[$parent_cat_id]['order_summa'] = 0;
            }
            $categories[$parent_cat_id]['orders'][] = $order->id;
            $categories[$parent_cat_id]['order_summa'] += $order->order_summa;
        }
        $this->view->categories = $categories;
    }
    
    public function send_messagesAction() {
        $args = func_get_args();
        if ($this->request->isGet() && count($args) > 0) {
            $category_id = (int)$args[0];
            $category = Categories::findFirst($category_id);
            if (!$category) {
                $this->flashSession->error('Категория не существует: ' . $category_id);
                return $this->response->redirect('/product/orders');
            }
            $cat_ids = Categories::getChildIDs($category_id);
            $cat_ids[] = $category_id;
            $this->view->users = $this->modelsManager->createBuilder()
                    ->from(array('o' => 'SpOrder', 'p' => 'Product'))
                    ->inWhere('p.category_id', $cat_ids)
                    ->andWhere('o.is_approved = ?1', array( 1 => 1))
                    ->andWhere('o.order_status_id != ?2', array( 2 => 3))
                    ->andWhere('o.product_id = p.id')
                    ->groupBy('user_id')
                    ->getQuery()
                    ->execute();
            if (count($this->view->users) < 1) {
                $this->flashSession->error('Некому отправлять сообщения в этой категории');
                return $this->response->redirect('/product/orders');
            }
            $this->tag->setDefault('category_id', $category_id);
            $this->tag->setDefault('users_count', print_r(join(',',$cat_ids),1));
        } elseif ($this->request->isPost() && $this->request->hasPost('category_id') && $this->request->hasPost('msg') ) {
            $category_id = (int)$this->request->getPost('category_id', 'int');
            $cat_ids = Categories::getChildIDs($category_id);
            $cat_ids[] = $category_id;
            $this->view->users = $this->modelsManager->createBuilder()
                    ->from(array('o' => 'SpOrder', 'p' => 'Product'))
                    ->inWhere('p.category_id', $cat_ids)
                    ->andWhere('o.is_approved = ?1', array( 1 => 1))
                    ->andWhere('o.order_status_id != ?2', array( 2 => 3))
                    ->andWhere('o.product_id = p.id')
                    ->groupBy('user_id')
                    ->getQuery()
                    ->execute();
            $this->tag->setDefault('category_id', $category_id);
            $this->tag->setDefault('users_count', print_r(join(',',$cat_ids),1));
            $to_users = $this->request->getPost('to_users');
            if (!is_array($to_users) || count($to_users) < 1) {
                $this->flashSession->error('Не выбраны пользователи');
                return true;
            }
            
            $msg = trim($this->request->getPost('msg', 'string'));
            if ($msg === '') {
                $this->flashSession->error('Пустое сообщение');
                return;
            }
            $category = Categories::findFirst($category_id);
            if (!$category) {
                $this->flashSession->error('Категория не существует');
                return $this->response->redirect('/product/orders');
            }
            $cat_ids = Categories::getChildIDs($category_id);
            $cat_ids[] = $category_id;
            $orders = $this->modelsManager->createBuilder()
                    ->from(array('o' => 'SpOrder', 'p' => 'Product'))
                    ->inWhere('p.category_id', $cat_ids)
                    ->andWhere('o.is_approved = ?1', array( 1 => 1))
                    ->andWhere('o.order_status_id != ?2', array( 2 => 3))
                    ->andWhere('o.product_id = p.id')
                    ->getQuery()
                    ->execute();
            if (count($orders) < 1) {
                $this->flashSession->error('В категории не найдено ни одного заказа');
                return $this->response->redirect('/product/orders');
            }
            $users = $this->modelsManager->createBuilder()
                    ->from('User')
                    ->inWhere('id', $to_users)
                    ->getQuery()
                    ->execute();
            foreach ($users as $cur_user) {
                $new_message = new UserMessage();
                $new_message->from_user_id = 0;
                $new_message->to_user_id = $cur_user->id;
                $new_message->item_datetime = new Phalcon\Db\RawValue('NOW()');
                $new_message->is_new = true;
                $new_message->msg_subject = 'Сообщение по закупке "' . $category->title . '"';
                $new_message->category_id = $category_id;
                $new_message->msg = $msg;
                
                if (!$new_message->save()) {
                    $this->flashSession->error('Ошибка: не удалось отправить сообщение пользователю: ' . User::getLogin($new_message->to_user_id));
                    foreach ($new_message->getMessages() as $message) {
                        $this->flashSession->error($message);
                    }
                }
            }
            $this->flashSession->success('Сообщение отправлено');
            return $this->response->redirect('/product/orders');
        } else {
            $this->flashSession->error('Не верные параметры для отправки сообщения по закупке');
            return $this->response->redirect('/product/orders');
        }
    }
    
    public function mark_paidAction() {
        $args = func_get_args();
        if ($this->request->isGet() && count($args) > 0) {
            $category_id = (int)$args[0];
            $category = Categories::findFirst($category_id);
            if (!$category) {
                $this->flashSession->error('Категория не существует: ' . $category_id);
                return $this->response->redirect('/product/orders');
            }
            $cat_ids = Categories::getChildIDs($category_id);
            $cat_ids[] = $category_id;
            $this->view->users = $this->modelsManager->createBuilder()
                    ->from(array('o' => 'SpOrder', 'p' => 'Product'))
                    ->inWhere('p.category_id', $cat_ids)
                    ->andWhere('o.is_approved = ?1', array( 1 => 1))
                    ->andWhere('o.order_status_id != ?2', array( 2 => 3))
                    ->andWhere('o.product_id = p.id')
                    ->groupBy('user_id')
                    ->getQuery()
                    ->execute();
            if (count($this->view->users) < 1) {
                $this->flashSession->error('Некому поютверждать оплату в этой категории');
                return $this->response->redirect('/product/orders');
            }
            $this->tag->setDefault('category_id', $category_id);
            $this->tag->setDefault('users_count', print_r(join(',',$cat_ids),1));
        } elseif ($this->request->isPost() && $this->request->hasPost('category_id')) {
            $category_id = (int)$this->request->getPost('category_id', 'int');
            $cat_ids = Categories::getChildIDs($category_id);
            $cat_ids[] = $category_id;
            $this->view->users = $this->modelsManager->createBuilder()
                    ->from(array('o' => 'SpOrder', 'p' => 'Product'))
                    ->inWhere('p.category_id', $cat_ids)
                    ->andWhere('o.is_approved = 1')
                    ->andWhere('o.order_status_id != 3')
                    ->andWhere('o.product_id = p.id')
                    ->groupBy('user_id')
                    ->getQuery()
                    ->execute();
            $this->tag->setDefault('category_id', $category_id);
            $this->tag->setDefault('users_count', print_r(join(',',$cat_ids),1));
            $to_users = $this->request->getPost('to_users');
            if (!is_array($to_users) || count($to_users) < 1) {
                $this->flashSession->error('Не выбраны пользователи');
                return true;
            }
            
            $category = Categories::findFirst($category_id);
            if (!$category) {
                $this->flashSession->error('Категория не существует');
                return $this->response->redirect('/product/orders');
            }
            $cat_ids = Categories::getChildIDs($category_id);
            $cat_ids[] = $category_id;
            $orders = $this->modelsManager->createBuilder()
                    ->from(array('o' => 'SpOrder', 'p' => 'Product'))
                    ->inWhere('p.category_id', $cat_ids)
                    ->andWhere('o.is_approved = 1')
                    ->andWhere('o.order_status_id != 3')
                    ->andWhere('o.product_id = p.id')
                    ->getQuery()
                    ->execute();
            if (count($orders) < 1) {
                $this->flashSession->error('В категории не найдено ни одного заказа');
                return $this->response->redirect('/product/orders');
            }

            foreach ($orders as $cur_order) {
                $cur_order->o->is_paid = true;
                
                if (!$cur_order->o->save()) {
                    $this->flashSession->error('Ошибка: не удалось подтвердить оплату пользователю: ' . User::getLogin($cur_order->o->user_id) . ' по заказу: ' . $cur_order->o->id);
                    foreach ($cur_order->getMessages() as $message) {
                        $this->flashSession->error($message);
                    }
                }
            }
            $this->flashSession->success('Оплата подтверждена');
            return $this->response->redirect('/product/orders');
        } else {
            $this->flashSession->error('Не верные параметры для подтверждения оплаты по закупке');
            return $this->response->redirect('/product/orders');
        }
    }
    
    public function paymentsAction() {
        $this->view->title = 'Сообщения об оплате';
        $this->tag->appendTitle(' - Сообщения об оплате');
        $orders_messages = OrderMessage::find(array(
                    'order' => 'item_datetime DESC'
        ));
        $this->view->messages = $orders_messages;
    }
    
    public function attrsAction() {
        $args = func_get_args();
        if (count($args) > 0 && $this->request->isGet()) {
            $id = (int)$args[0];
            $product = Product::findFirst($id);
            if ($product) {
                $this->view->product_attributes = ProductAttribute::find(array(
                    'conditions' => 'product_id = ?1',
                    'bind' => array(
                        1 => $id
                    )
                ));
                $this->view->product_id = $product->id;
                $this->view->product = $product;
                return;
            }
            $this->flashSession->error('Ошибка: попытка изменения размеров для несуществующего товара');
        } 
        $this->flashSession->error('Ошибка изменеиня товара');
        return $this->dispatcher->forward(array(
                    'controller' => 'categories',
                    'action' => 'index'
        ));
    }
    
    public function add_attrAction() {
        if ($this->request->isPost() && $this->request->hasPost('product_attr_temlpate') && 
                $this->request->hasPost('product_id')) 
            {
            $product_id = $this->request->getPost('product_id', 'int');
            $product = Product::findFirst($product_id);
            if (!$product) {
                $this->flashSession->error('Товар не существует');
                return $this->response->redirect('/product/attrs/' . $product_id);
            }
            $product_attributes = $this->request->getPost('product_attr_temlpate', 'string');
            if (count($product_attributes) < 1) {
                $this->flashSession->error('Не указано размеров для добавления');
                return $this->response->redirect('/product/attrs/' . $product_id);
            }
            foreach ($product_attributes as $cur_attr) {
                if (trim($cur_attr) == '') {
                    continue;
                }
                $existing_attr = ProductAttribute::findFirst(array(
                            'conditions' => 'product_id = ?1 AND attr = ?2',
                            'bind' => array(
                                1 => $product->id,
                                2 => trim($cur_attr)
                            )
                ));
                if ($existing_attr) {
                    $this->flashSession->error('Предупреждение: такой размер уже существует для сохраняемого товара. Размер: ' . $cur_attr);
                    continue;
                }
                $product_attribute = new ProductAttribute();
                $product_attribute->attr = $cur_attr;
                $product_attribute->product_id = $product->id;
                if (!$product_attribute->save()) {
                    $this->flashSession->error('Ошибка: не удалось сохранить размер для товара. Размер: ' . $cur_attr);
                    foreach ($product_attribute->getMessages() as $message) {
                        $this->flashSession->error($message);
                    }
                }
            }
            return $this->response->redirect('/product/attrs/' . $product_id);
        }
        $this->flashSession->error('Не верные параметры при добавлении размера товара');
        return $this->response->redirect('/categories/');
    }
    
    public function remove_attrAction() {
        $args = func_get_args();
        if (count($args) > 1 && $this->request->isGet()) {
            $product_id = (int)$args[0];
            $attr_id = (int)$args[1];
            $product_attr = ProductAttribute::findFirst(array(
                'conditions' => 'product_id = ?1 AND id = ?2',
                'bind' => array(
                    1 => $product_id,
                    2 => $attr_id
                )
            ));
            if ($product_attr) {
                if (!$product_attr->delete()) {
                    $this->flashSession->error('Не удалось удалить размер');
                    return $this->response->redirect('/product/attrs/' . $product_id);
                }
                $this->flashSession->success('Размер удален');
                return $this->response->redirect('/product/attrs/' . $product_id);
            }
            $this->flashSession->error('Ошибка: попытка удаления несуществующего размера');
        } 
        $this->flashSession->error('Ошибка удаления размера');
        return $this->dispatcher->forward(array(
                    'controller' => 'categories',
                    'action' => 'index'
        ));
    }
    
    public function viewAction() {
        $args = func_get_args();
        if (count($args) > 0 && $this->request->isGet()) {
            $id = (int)$args[0];
            $this->view->product = Product::findFirst(array(
                "conditions" => "id = ?1",
                "bind" => array(1 => (int)$id)
            ));
            if ($this->view->product) {
                $this->view->product_img = ProductImage::getProductImage($id);
                $this->view->product_attributes = ProductAttribute::find(array(
                    'conditions' => 'product_id = ?1',
                    'bind' => array(
                        1 => (int)$id
                    )
                ));
                return;
            }
        }
        $this->flashSession->error('Товар не существует');
        return $this->dispatcher->forward(array(
                    'controller' => 'categories',
                    'action' => 'index'
        ));
    }
    
    public function msgAction() {
        $args = func_get_args();
        if (count($args) == 1 && $this->request->isGet()) {
            $order_id = (int)$args[0];
            $this->view->order = SpOrder::findFirst((int)$args[0]);
        } else {
            $this->flashSession->error('Ошибка при попытке оставить сообщение по заказу');
            return $this->response->redirect('/profile/index');
        }
    }
    
    public function save_msgAction() {
        if ($this->request->isPost()) {
            $order_id = $this->request->getPost('order_id', 'int');
            $msg = trim($this->request->getPost('msg', 'string'));
            $order = SpOrder::findFirst($order_id);
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
		$this->flashSession->error('Заказ не принят. Администратор не должен делать заказы');
        $this->flashSession->error('Ошибка при совершении заказа');
        return $this->response->redirect('/categories/');
    }
    
    public function editAction() {
        $args = func_get_args();
        if (count($args) > 0 && $this->request->isGet()) {
            $id = (int)$args[0];
            $product = Product::findFirst($id);
            if ($product) {
                $this->view->product_id = $product->id;
                $this->tag->setDefault('product_title', $product->title);
                $this->tag->setDefault('product_description', $product->description);
                $this->tag->setDefault('product_price', $product->price);
                $this->view->product_img = ProductImage::getProductImage($product->id);
                return $this->dispatcher->forward(array(
                    'controller' => 'product',
                    'action' => 'add',
                    'params' => array($product->category_id)
                ));
            }
            $this->flashSession->error('Ошибка изменения товара');
        } 
        $this->flashSession->error('Ошибка изменения товара');
        return $this->dispatcher->forward(array(
                    'controller' => 'categories',
                    'action' => 'index'
        ));
    }
    
    public function addAction() {
        $args = func_get_args();
        if (count($args) > 0 && $this->request->isGet()) {
            $id = (int)$args[0];
            $category_child_cats = Categories::find(array(
                'conditions' => 'parent_category_id = ?1',
                'bind' => array(
                    1 => $id
                )
            ));
            if ($category_child_cats && count($category_child_cats)) {
                $this->flashSession->error('Нельзя добавить товар в категорию, которая имеет подкатегории');
                return $this->response->redirect('/categories/view/' . $id);
            }
            $category = Categories::findFirst($id);
            if ($category) {
                $this->view->category_id = $id;
                return;
            }
        }
        $this->flashSession->error('Ошибка добавления товара. Не выбрана закупка');
        return $this->dispatcher->forward(array(
                    'controller' => 'categories',
                    'action' => 'index'
        ));
    }
    
    public function saveAction() {
        if ($this->request->isPost() && $this->request->hasPost('category_id')) {
            $category_id = $this->request->getPost('category_id', 'int');
            $category = Categories::findFirst($category_id);
            if (!$category) {
                $this->flashSession->error('Не найдена такая закупка товаров');
                return $this->dispatcher->forward(array(
                            'controller' => 'product',
                            'action' => 'add'
                ));
            }
            if ($this->request->hasFiles() != true) {
                $img_data = new Phalcon\Db\RawValue('default');
                $img_big_data = NULL;
                $small_img_data = NULL;
            } else {
                foreach ($this->request->getUploadedFiles() as $file) {
                    $product_img = $file;
                }
                $thumb = new Imagick();
                $thumb->readImage($file->getTempName());
                $thumb->resizeImage(1024,800, imagick::FILTER_LANCZOS, 1, true);
                $thumb->writeImage($file->getTempName());
                $img_big_data = file_get_contents($file->getTempName());
                $thumb->resizeImage(200,200, imagick::FILTER_LANCZOS, 1, true);
                $thumb->writeImage($file->getTempName());
                $thumb->clear();
                $thumb->destroy(); 
                $img_data = base64_encode(file_get_contents($file->getTempName()));
                $small_img_data = file_get_contents($file->getTempName());
            }
            if (!$this->request->hasPost('product_title') || !$this->request->hasPost('product_description')) {
                $this->flashSession->error('Не указано название либо описание товара');
                return $this->dispatcher->forward(array(
                            'controller' => 'product',
                            'action' => 'add'
                ));
            }

            $product_title = trim($this->request->getPost('product_title', 'string'));
            $product_attributes = $this->request->getPost('product_attr', 'string');
            $product_price = $this->request->getPost('product_price', 'float');
            $product_description = strip_tags(trim($this->request->getPost('product_description')), '<p><br><i><b>');
            $this->db->begin();
            if ($this->request->hasPost('add_product')) {
                $product = new Product();
                $product->category_id = $category_id;
                $product->title = $product_title;
                $product->description = $product_description;
                $product->price = $product_price;
                
                if (!is_array($product_attributes) || count($product_attributes) < 1) {
                    $this->db->rollBack();
                    $this->flashSession->error('Ошибка обновления товара - не указаны размеры');
                    return $this->response->redirect('/categories/view/' . $category_id);
                }
                
                $productImg = new ProductImage();
                $productImg->img_data = $img_data;
                
            } elseif ($this->request->hasPost('update_product')) {
                if (!$this->request->hasPost('product_id')) {
                    $this->db->rollBack();
                    $this->flashSession->error('Ошибка обновления товара - отсутствует ID');
                    return $this->response->redirect('/categories/view/' . $category_id);
                }
                $product_id = $this->request->getPost('product_id', 'int');
                if ($this->request->hasPost('product_img') && strlen($this->request->getPost('product_img', 'string'))) {
                    $product_prev_img = $this->request->getPost('product_img', 'string');
                }
                $product = Product::findFirst($product_id);
                        
                if (!$product) {
                    $this->db->rollBack();
                    $this->flashSession->error('Ошибка обновления товара - закупка не найдена');
                    return $this->dispatcher->forward(array(
                        'controller' => 'product',
                        'action' => 'add'
                    ));
                }
                $image_file_name = __DIR__ . '/../../public/img/products/img_' . $product->category_id . '_' .$product->id . '.jpg';
                $small_image_file_name = __DIR__ . '/../../public/img/products/img_sm_' . $product->category_id . '_' .$product->id . '.jpg';
                
                if (file_exists($image_file_name)) {
                    unlink($image_file_name);
                }
                if (file_exists($small_image_file_name)) {
                    unlink($small_image_file_name);
                }
                
                $product->category_id = $category_id;
                $product->title = $product_title;
                $product->price = $product_price;
                $product->description = $product_description;
                if (isset($product_prev_img)) {
                    $product->img = $product_prev_img;
                } else {
                    $product->img = $img_data;
                }
                $productImg = ProductImage::findFirst(array(
                    'conditions' => 'product_id = ?1',
                    'bind' => array(
                        1 => $product_id
                    )
                ));
                if (!$productImg) {
                    $productImg = new ProductImage();
                }
                $productImg->img_data = $product->img;
                $productImg->product_id = $product_id;
            }
                        
            if (!$product->save()) {
                $this->db->rollBack();
                $this->flashSession->error('Ошибка сохранения товара');
                foreach ($product->getMessages() as $message) {
                    $this->flashSession->error($message);
                }
                return $this->response->redirect('/categories/index/');
            } else {
                $productImg->product_id = $product->id;
                if (!$productImg->save()) {
                    $this->flashSession->error('Предупреждение: не удалось сохранить изображение товара');
                    foreach ($productImg->getMessages() as $message) {
                        $this->flashSession->error($message);
                    }
                }
                
                if ($this->request->hasPost('add_product')) {
                    foreach ($product_attributes as $cur_attr) {
                        $existing_attr = ProductAttribute::findFirst(array(
                            'conditions' => 'product_id = ?1 AND attr = ?2',
                            'bind' => array(
                                1 => $product->id,
                                2 => trim($cur_attr)
                            )
                        ));
                        if ($existing_attr) {
                            $this->flashSession->error('Предупреждение: такой размер уже существует для сохраняемого товара');
                            continue;
                        }
                        $product_attribute = new ProductAttribute();
                        $product_attribute->attr = $cur_attr;
                        $product_attribute->product_id = $product->id;
                        if (!$product_attribute->save()) {
                            $this->flashSession->error('Ошибка: не удалось сохранить размер для товара');
                            foreach ($product_attribute->getMessages() as $message) {
                                $this->flashSession->error($message);
                            }
                            $this->db->rollBack();
                            return $this->response->redirect('/categories/view/' . $category_id);
                        }
                    }
                }
                
                if (!is_null($img_big_data)) {
                    file_put_contents(__DIR__ . '/../../public/img/products/img_' . $product->category_id . '_' .$product->id . '.jpg', $img_big_data);
                }
                if (!is_null($small_img_data)) {
                    file_put_contents(__DIR__ . '/../../public/img/products/img_sm_' . $product->category_id . '_' .$product->id . '.jpg', $small_img_data);
                }
                
                $this->db->commit();
                $this->flashSession->success('Товар сохранен');
                return $this->response->redirect('/categories/view/' . $category_id);
            }
        }
        return $this->dispatcher->forward(array(
                    'controller' => 'signup',
                    'action' => 'index'
        ));
    }
    
}
