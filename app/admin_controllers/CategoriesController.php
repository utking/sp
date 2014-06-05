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
        if (count($args) > 0 && $this->request->isGet()) {
            $id = (int)$args[0];
            $this->view->category = Categories::findFirst(array(
                "conditions" => "id = ?1",
                "bind" => array(1 => (int)$id)
            ));
            $this->view->category_child_cats = Categories::find(array(
                "conditions" => "parent_category_id = ?1",
                "bind" => array(1 => (int)$id)
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
}