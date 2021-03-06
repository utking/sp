<?php

use Phalcon\Db\RawValue;

class UsersController extends ControllerBase {
    
    protected function initialize() {
        $this->tag->prependTitle('Пользователи');
        parent::initialize();
    }

    public function indexAction() {
        $this->view->users = User::find(array(
            'order' => 'login'
        ));
        $this->view->active_users = User::find(array(
            'conditions' => 'is_alive = 1'
        ));
        $this->view->inactive_users = User::find(array(
            'conditions' => 'is_alive = 0'
        ));
    }
    
    public function viewAction() {
        $args = func_get_args();
        if (count($args) > 0 && $this->request->isGet()) {
            $id = (int)$args[0];
            $this->view->category = Categories::findFirst(array(
                "conditions" => "id = ?1",
                "bind" => array(1 => (int)$id)
            ));
			$this->tag->setDefault('category_id', $id);
            $this->view->category_child_cats = Categories::find(array(
                "conditions" => "parent_category_id = ?1",
                "bind" => array(1 => (int)$id)
            ));
            $this->view->forum_msgs = ForumMessage::find(array(
                'conditions' => 'category_id = ?1',
                "bind" => array(1 => (int)$id),
                'order' => 'item_datetime DESC'
            ));
            if ($this->view->category) {
                $this->view->products = Product::find(array(
                    "conditions" => "category_id = ?1",
                    "bind" => array(1 => (int)$id)
                ));
                return;
            }
        }
        $this->flashSession->error('Закупка не существует');
        return $this->dispatcher->forward(array(
                    'controller' => 'categories',
                    'action' => 'index'
        ));
    }
    
    public function addAction() {
        $args = func_get_args();
        if (count($args) > 0) {
            $this->view->parents = Categories::find(array(
                'conditions' => 'id != ?1',
                'bind' => array(
                    1 => (int)$args[0]
                )
            ));
        } else {
            $this->view->parents = Categories::find();
        }
    }
    
    public function editAction() {
        $args = func_get_args();
        if (count($args) > 0 && $this->request->isGet()) {
            $id = (int)$args[0];
            $category = Categories::findFirst($id);
            if ($category) {
                $this->view->category_id = $category->id;
                $this->tag->setDefault('category_title', $category->title);
                $this->tag->setDefault('category_description', $category->desc);
                $this->tag->setDefault('category_rules', $category->rules);
                $this->tag->setDefault('category_hidden', $category->hidden);
                $this->tag->setDefault('category_use_forum', $category->use_forum);
                $stop_datetime = DateTime::createFromFormat('Y-m-d H:i:s', $category->stop_datetime);
                $this->tag->setDefault('category_stop_datetime', $stop_datetime->format('d.m.Y H:i'));
                $this->view->stop_datetime = $stop_datetime;
                $this->view->category_img = $category->img;
                $this->tag->setDefault('category_parent_id', $category->parent_category_id);
                return $this->dispatcher->forward(array(
                            'controller' => 'categories',
                            'action' => 'add',
                            'params' => array(
                                1 => $category->id
                            )
                ));
            }
            $this->flashSession->error('Ошибка изменеиня закупки');
        } 
        $this->flashSession->error('Ошибка изменеиня закупки');
        return $this->dispatcher->forward(array(
                    'controller' => 'categories',
                    'action' => 'index'
        ));
    }
    
    public function saveAction() {
        if ($this->request->isPost()) {
            if ($this->request->hasFiles() != true) {
                $img_data = new Phalcon\Db\RawValue('default');
            } else {
                foreach ($this->request->getUploadedFiles() as $file) {
                    $category_img = $file;
                }
                $thumb = new Imagick();
                $thumb->readImage($file->getTempName());
                $thumb->resizeImage(250,250, imagick::FILTER_LANCZOS, 0.9, true);
                $thumb->writeImage($file->getTempName());
                $thumb->clear();
                $thumb->destroy(); 
                $img_data = base64_encode(file_get_contents($file->getTempName()));
            }
            if (!$this->request->hasPost('category_title') || !$this->request->hasPost('category_description')) {
                $this->flashSession->error('Не указано название либо описание закупки');
                return $this->dispatcher->forward(array(
                            'controller' => 'categories',
                            'action' => 'add'
                ));
            }

            $category_parent_id = (int)$this->request->getPost('category_parent_id', 'int');
            $category_hidden = (int)$this->request->hasPost('category_hidden');
            $category_use_forum = (int)$this->request->hasPost('category_use_forum');
            $category_title = trim($this->request->getPost('category_title', 'string'));
            $category_description = strip_tags(trim($this->request->getPost('category_description')), '<p><br><i><b>');
            $category_rules = strip_tags(trim($this->request->getPost('category_rules')), '<p><br><i><b>');
            $stop_datetime = trim($this->request->getPost('category_stop_datetime', 'string'));
            $category_stop_datetime = DateTime::createFromFormat('d.m.Y H:i', $stop_datetime);
            
            if (!is_object($category_stop_datetime)) {
                $this->flashSession->error('Дата стопа задана не верно');
                if ($this->request->hasPost('add_category')) {
                    return $this->dispatcher->forward(array(
                                'controller' => 'categories',
                                'action' => 'add'
                    ));
                } elseif ($this->request->hasPost('update_category')) {
                    $category_id = $this->request->getPost('category_id', 'int');
                    return $this->response->redirect('/categories/edit/' . $category_id);
                }
            }
            
            $exist_parent_cat = Categories::findFirst($category_parent_id);
            if (!$exist_parent_cat && $category_parent_id) {
                $this->flashSession->error('Родительская категория не существует');
                if ($this->request->hasPost('add_category')) {
                    return $this->dispatcher->forward(array(
                                'controller' => 'categories',
                                'action' => 'add'
                    ));
                } elseif ($this->request->hasPost('update_category')) {
                    $category_id = $this->request->getPost('category_id', 'int');
                    return $this->response->redirect('/categories/edit/' . $category_id);
                }
            }
            
            $parent_cat_products = Product::find(array(
                'conditions' => 'category_id = ?1',
                'bind' => array(
                    1 => $category_parent_id
                )
            ));
            
            if ($parent_cat_products && count($parent_cat_products)) {
                $this->flashSession->error('Родительская категория не должна содержать товаров');
                $this->flashSession->error(count($parent_cat_products));
                if ($this->request->hasPost('add_category')) {
                    return $this->dispatcher->forward(array(
                                'controller' => 'categories',
                                'action' => 'add'
                    ));
                } elseif ($this->request->hasPost('update_category')) {
                    $category_id = $this->request->getPost('category_id', 'int');
                    return $this->response->redirect('/categories/edit/' . $category_id);
                }
            }
            
            if ($this->request->hasPost('add_category')) {
                $exist_cat = Categories::findFirst(array(
                    'conditions' => 'title = ?1',
                    'bind' => array(
                        1 => $category_title
                    )
                ));
                if ($exist_cat) {
                    $this->flashSession->error('Закупка уже существует');
                    return $this->dispatcher->forward(array(
                                'controller' => 'categories',
                                'action' => 'add'
                    ));
                }
                $category = new Categories();
                $category->parent_category_id = $category_parent_id;
                $category->title = $category_title;
                $category->desc = $category_description;
                $category->rules = $category_rules;
                $category->img = $img_data;
                $category->hidden = $category_hidden;
                $category->use_forum = $category_use_forum;
                $category->stop_datetime = $category_stop_datetime->format('Y-m-d H:i');
                
            } elseif ($this->request->hasPost('update_category')) {
                if (!$this->request->hasPost('category_id')) {
                    $this->flashSession->error('Ошибка обновления закупки - отсутствует ID');
                    return $this->dispatcher->forward(array(
                                'controller' => 'categories',
                                'action' => 'list'
                    ));
                }
                $category_id = $this->request->getPost('category_id', 'int');
                if ($this->request->hasPost('category_img') && strlen($this->request->getPost('category_img', 'string'))) {
                    $category_prev_img = $this->request->getPost('category_img', 'string');
                }
                $category = Categories::findFirst($category_id);
                if (!$category) {
                    $this->flashSession->error('Ошибка обновления закупки - закупка не найдена');
                    return $this->dispatcher->forward(array(
                        'controller' => 'categories',
                        'action' => 'add'
                    ));
                }
                $category->parent_category_id = $category_parent_id;
                $category->title = $category_title;
                $category->desc = $category_description;
                $category->rules = $category_rules;
                $category->hidden = $category_hidden;
                $category->use_forum = $category_use_forum;
                if (isset($category_prev_img)) {
                    $category->img = $category_prev_img;
                } else {
                    $category->img = $img_data;
                }
                $category->stop_datetime = $category_stop_datetime->format('Y-m-d H:i');
            }
            
            if (!$category->save()) {
                $this->flashSession->error('Ошибка сохранения закупки');
                foreach ($category->getMessages() as $message) {
                    $this->flashSession->error($message);
                }
                if ($this->request->hasPost('add_category')) {
                    return $this->dispatcher->forward(array(
                                'controller' => 'categories',
                                'action' => 'add'
                    ));
                } elseif ($this->request->hasPost('update_category')) {
                    return $this->response->redirect('/categories/edit/' . $category_id);
                }
            } else {
                $this->flashSession->success('Закупка сохранена');
                if ($category->parent_category_id) {
                    return $this->response->redirect('/categories/view/' . $category->parent_category_id);
                } else {
                    return $this->response->redirect('/categories/');
                }
            }
            
        }
        return $this->dispatcher->forward(array(
                    'controller' => 'signup',
                    'action' => 'index'
        ));
    }
    
    public function drop_productsAction() {
		$category_id = (int)$this->request->getPost('category_id', 'int');
        if ($this->request->isPost() && $this->request->hasPost('drop_products') && $this->request->hasPost('uid')) {
            $uids = $this->request->getPost('uid', 'int');
			$this->db->begin();
            foreach ($uids as $item) {
				$prod_attrs = ProductAttribute::find(array(
					'conditions' => 'product_id = ?1',
					'bind' => array(
						1 => $item
					)
				));
				$product = Product::findFirst($item);
				if (!$product || $product->category_id != $category_id) {
					$this->flashSession->error('Товар не найден');
					$this->db->rollBack();
					return $this->response->redirect('/categories/view/' . $category_id);
				}
				foreach ($prod_attrs as $attr) {
					if (!$attr->delete()) {
						$this->db->rollBack();
						foreach ($attr->getMessages() as $message) {
							$this->flashSession->error($message);
						}
						return $this->response->redirect('/categories/view/' . $category_id);
					}
				}
				if (!$product->delete()) {
					$this->flashSession->error('Товар не удален');
					$this->db->rollBack();
					foreach ($product->getMessages() as $message) {
						$this->flashSession->error($message);
					}
					return $this->response->redirect('/categories/view/' . $category_id);
				}
            }

			$this->db->commit();
			$this->flashSession->success('Товары удалены');
			return $this->response->redirect('/categories/view/' . $category_id);
        } else {
			$this->flashSession->error('Не верные параметры');
			return $this->response->redirect('/categories/view/' . $category_id);
		}
    }
    
    public function new_messageAction() {
        
        $auth = $this->session->get('auth');
        
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
