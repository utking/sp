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
    
    public function remove_forum_msgAction() {
        $result = new stdClass();
        if ($this->request->isPost() && $this->request->hasPost('remove_forum_msg') && $this->request->hasPost('id')) {
            $msg_id = $this->request->getPost('id', 'int');
            $msg = ForumMessage::findFirst($msg_id);
            if (!$msg) {
                $result->hasError = true;
                $result->errorMsg = "Сообщение не найдено в базе";
                die(json_encode($result));
            }
            if (!$msg->delete()) {
                $result->hasError = true;
                $result->errorMsg = "Сообщение не удалено: " . PHP_EOL;
                foreach ($msg->getMessages() as $errMessage) {
                    $result->errorMsg += $errMessage . PHP_EOL;
                }
                die(json_encode($result));
            }
            $result->hasError = false;
            die(json_encode($result));
        }
        $result->hasError = true;
        $result->errorMsg = "Не верные параметры запроса на удаление";
        die(json_encode($result));
    }

    public function viewAction() {
        $args = func_get_args();
        if (count($args) > 0 && $this->request->isGet()) {
            $id = (int) $args[0];
            $this->view->category = Categories::findFirst(array(
                        "conditions" => "id = ?1",
                        "bind" => array(1 => (int) $id)
            ));
            $this->tag->setDefault('category_id', $id);
            $this->view->category_child_cats = Categories::find(array(
                        "conditions" => "parent_category_id = ?1",
                        "bind" => array(1 => (int) $id)
            ));
            $this->view->forum_msgs = ForumMessage::find(array(
                        'conditions' => 'category_id = ?1',
                        "bind" => array(1 => (int) $id),
                        'order' => 'item_datetime DESC'
            ));
            if ($this->view->category) {
                $this->view->products = Product::find(array(
                            "conditions" => "category_id = ?1",
                            "bind" => array(1 => (int) $id)
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
                            1 => (int) $args[0]
                        )
            ));
        } else {
            $this->view->parents = Categories::find();
        }
    }

    public function editAction() {
        $args = func_get_args();
        if (count($args) > 0 && $this->request->isGet()) {
            $id = (int) $args[0];
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
                $thumb->resizeImage(250, 250, imagick::FILTER_LANCZOS, 1, true);
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

            $category_parent_id = (int) $this->request->getPost('category_parent_id', 'int');
            $category_hidden = (int) $this->request->hasPost('category_hidden');
            $category_use_forum = (int) $this->request->hasPost('category_use_forum');
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
        $category_id = (int) $this->request->getPost('category_id', 'int');
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
                if (file_exists(__DIR__ . '/../../public/img/products/img_' . $product->category_id . '_' . $product->id . '.jpg')) {
                    unlink(__DIR__ . '/../../public/img/products/img_' . $product->category_id . '_' . $product->id . '.jpg');
                }
                if (file_exists(__DIR__ . '/../../public/img/products/img_sm_' . $product->category_id . '_' . $product->id . '.jpg')) {
                    unlink(__DIR__ . '/../../public/img/products/img_sm_' . $product->category_id . '_' . $product->id . '.jpg');
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

    public function load100spAction() {
        ;
    }

    public function fetch100spAction() {
        if ($this->request->isPost() && $this->request->hasPost('collection_id')) {
            $collection_id = (int) $this->request->getPost('collection_id', 'int');
            if ($collection_id > 0) {
                $address = 'http://www.100sp.ru/collection.php?cid=' . $collection_id;
                $file_name = 'fetcher.gz';

                $cmd = "wget '$address' " .
                        " --header 'Accept-Language: en-US,en;q=0.5' " .
                        " --header 'Cache-Control: no-cache' " .
                        " --header 'Connection: keep-alive' " .
                        " --header 'User-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:29.0) Gecko/20100101 Firefox/29.0 Iceweasel/29.0.1' " .
                        " --header 'X-Requested-With: XMLHttpRequest' " .
                        " -O " . __DIR__ . '/../../app/tmp/' . $file_name;

                exec($cmd);
                $ret = file_get_contents(__DIR__ . '/../../app/tmp/' . $file_name);
                $data = $ret;

                $html = $this->simple_html_dom;
                $html->load($data);

                $obj = $html->find('table');

                $items = [];
                $i = 0;

                foreach ($obj as $table) {
                    $rows = $table->find('tr[class=goods_list]');
                    foreach ($rows as $row) {
                        $items[$i] = [];
                        $items[$i]['name'] = trim($row->children[1]->plaintext);
                        $items[$i]['price'] = str_replace(',', '.', trim($row->children[3]->plaintext));
                        $items[$i]['desc'] = trim($row->children[4]->plaintext);
                        $items[$i]['img_addr'] = trim($row->children[2]->find('a', 0)->find('img', 0)->src);
                        $items[$i]['details_addr'] = 'http://www.100sp.ru/' . trim($row->children[2]->find('a', 0)->href);
                        $items[$i]['img_addr'] = str_replace('cache_', '', $items[$i]['img_addr']);
                        $items[$i]['img_addr'] = str_replace('/thumb150', '', $items[$i]['img_addr']);
                        $sizes = [];
                        foreach ($row->find('td[class=sizes_orders]', 0)->find('tr') as $size_row) {
                            $sizes[] = trim($size_row->children[0]->plaintext);
                        }
                        $items[$i]['size'] = $sizes;

                        $i++;
                    }
                }
                $this->view->categories = Categories::find(array(
                            'order' => 'title'
                ));
                $this->view->items = $items;
                return true;
            }
        } elseif ($this->request->isGet()) {
            return true;
        }
        $this->flashSession->error('Ошибка загрузки. Неверные параметры');
        return $this->response->redirect('/categories/load100sp');
    }

    public function save100spAction() {
        if ($this->request->isPost() && $this->request->hasPost('items') && $this->request->hasPost('fetch_100sp_form') && $this->request->hasPost('category_id')) {

            $category_id = (int) $this->request->getPost('category_id', 'int');
            $category = Categories::findFirst($category_id);

            if ($category_id < 1) {
                $this->flashSession->error('Ошибка загрузки. Неверная категория');
                return $this->response->redirect('/categories/load100sp');
            }
            if (!$category) {
                $this->flashSession->error('Ошибка загрузки. Категория отсутствует');
                return $this->response->redirect('/categories/load100sp');
            }

            $items = $this->request->getPost('items');
            $item_imgs = $this->request->getPost('item_imgs');
            $item_names = $this->request->getPost('item_names');
            $item_prices = $this->request->getPost('item_prices');
            $item_descs = $this->request->getPost('item_descs');
            $this->db->begin();
            $prod_count = 0;
            foreach ($items as $item_pos => $item) {
                $product = new Product();
                $product->img = (isset($item_imgs[$item]) ? $item_imgs[$item] : NULL);
                if (!is_null($product->img)) {
                    $product->img_data = $this->loadImage100sp($product->img);
                }
                $product->category_id = $category_id;
                $product->title = (isset($item_names[$item]) ? $item_names[$item] : NULL);
                $product->price = (isset($item_prices[$item]) ? str_replace(',', '.', $item_prices[$item]) : NULL);
                $product->description = (isset($item_descs[$item]) ? $item_descs[$item] : NULL);
                $sizes = $this->request->getPost('item_size_' . $item);

                if (!$product->save() || count($sizes) < 1) {
                    $this->flashSession->error('Ошибка добавления товара');
                    foreach ($product->getMessages() as $message) {
                        $this->flashSession->error($message);
                    }
                    $this->db->rollBack();
                    return $this->response->redirect('/categories/load100sp');
                }

                foreach ($sizes as $cur_attr) {
                    $existing_attr = ProductAttribute::findFirst(array(
                                'conditions' => 'product_id = ?1 AND attr = ?2',
                                'bind' => array(
                                    1 => $product->id,
                                    2 => trim($cur_attr)
                                )
                    ));
                    if ($existing_attr) {
                        $this->flashSession->error('Предупреждение: такой размер уже существует для сохраняемого товара: ' . $cur_attr);
                        continue;
                    }
                    $product_attribute = new ProductAttribute();
                    $product_attribute->attr = $cur_attr;
                    $product_attribute->product_id = $product->id;
                    if (!$product_attribute->save()) {
                        $this->flashSession->error('Ошибка: не удалось сохранить размер для товара: ' . $product->title);
                        foreach ($product_attribute->getMessages() as $message) {
                            $this->flashSession->error($message);
                        }
                        $this->db->rollBack();
                        return $this->response->redirect('/categories/load100sp');
                    }
                }
                if (strlen($product->img_data) < 1) {
                    $img_big_data = NULL;
                    $small_img_data = NULL;
                } else {
                    $tmpHandle = tmpfile();
                    $metaDatas = stream_get_meta_data($tmpHandle);
                    $tmp_name = $metaDatas['uri'];
                    file_put_contents($tmp_name, $product->img_data);

                    $thumb = new Imagick();
                    $thumb->readImage($tmp_name);
                    $thumb->resizeImage(1024, 800, imagick::FILTER_LANCZOS, 1, true);
                    $thumb->writeImage($tmp_name);
                    $img_big_data = file_get_contents($tmp_name);
                    $thumb->resizeImage(200, 200, imagick::FILTER_LANCZOS, 1, true);
                    $thumb->writeImage($tmp_name);
                    $thumb->clear();
                    $thumb->destroy();
                    $small_img_data = file_get_contents($tmp_name);
                }

                if (!is_null($img_big_data)) {
                    file_put_contents(__DIR__ . '/../../public/img/products/img_' . $product->category_id . '_' . $product->id . '.jpg', $img_big_data);
                }
                if (!is_null($small_img_data)) {
                    file_put_contents(__DIR__ . '/../../public/img/products/img_sm_' . $product->category_id . '_' . $product->id . '.jpg', $small_img_data);
                    $productImg = new ProductImage();
                    $productImg->img_data = base64_encode($small_img_data);
                    $productImg->product_id = $product->id;
                    if (!$productImg->save()) {
                        $this->flashSession->error('Предупреждение: не удалось сохранить изображение товара ' . $product->title);
                        foreach ($productImg->getMessages() as $message) {
                            $this->flashSession->error($message);
                        }
                    }
                }
                $prod_count++;
            }
            $this->db->commit();
            $this->flashSession->success('Товары со 100sp загружены: ' . $prod_count);
            return $this->response->redirect('/categories/load100sp');
        }
        $this->flashSession->error('Ошибка загрузки. Неверные параметры');
        $this->flashSession->error('<pre>' . print_r($_POST, 1) . '</pre>');
        return $this->response->redirect('/categories/load100sp');
    }

    protected function loadImage100sp($image_addr) {
        $tmpHandle = tmpfile();
        $metaDatas = stream_get_meta_data($tmpHandle);
        $tmp_name = $metaDatas['uri'];
        $cmd = "wget '$image_addr' -O " . $tmp_name;

        exec($cmd);
        $ret = file_get_contents($tmp_name);
        return $ret;
    }
    
    public function view_messagesAction() {
        $auth = $this->session->get('auth');
        if (!isset($auth['id'])) {
            $this->flashSession->error('Войдите под своими учетными данными чтобы просмотреть сообщения.');
            return $this->response->redirect('/categories/');
        }
        $args = func_get_args();
        if (count($args) > 0 && $this->request->isGet()) {
            $id = (int) $args[0];
            $this->view->category = Categories::findFirst($id);
            $this->view->cat_messages = UserMessage::find(array(
                'conditions' => 'category_id = ?1 AND to_user_id = ?2',
                'bind' => array(
                    1 => $id,
                    2 => $auth['id']
                ),
                'order' => 'item_datetime DESC'
            ));
        } else {
            $this->flashSession->error('Не указана закупка для просмотра сообщений.');
            return $this->response->redirect('/categories/');
        }
    }
    
    public function remove_msgAction() {
        $result = new stdClass();
        $auth = $this->session->get('auth');
        if ($this->request->isPost() && $this->request->hasPost('remove_msg') && $this->request->hasPost('items')) {
            $items = $this->request->getPost('items', 'int');
            
            if (!isset($auth['id'])) {
                $result->hasError = true;
                $result->errorMsg = "Войдите под своими учетными данными";
                die(json_encode($result));
            }
            
            foreach ($items as $item) {
                $msg = AskAdminMessage::findFirst($item);
                if ($msg) {
                    $msg->delete();
                }
            }
                        
            $result->hasError = false;
            die(json_encode($result));
        }
        $result->hasError = true;
        $result->errorMsg = "Не верные параметры";
        die(json_encode($result));
    }
    
    public function send_responseAction() {
        $result = new stdClass();
        $auth = $this->session->get('auth');
        if ($this->request->isPost() && $this->request->hasPost('send_response') && $this->request->hasPost('category_id') && 
                $this->request->hasPost('message_id') && $this->request->hasPost('text')) {
            $category_id = $this->request->getPost('category_id', 'int');
            $message_id = $this->request->getPost('message_id', 'int');
            $msg = trim($this->request->getPost('text', 'string'));
            
            $category = Categories::findFirst($category_id);
            $question = UserMessage::findFirst($message_id);
            
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
            
            if (!$question) {
                $result->hasError = true;
                $result->errorMsg = "Вопрос не существует";
                die(json_encode($result));
            }
            
            if (!isset($auth['id'])) {
                $result->hasError = true;
                $result->errorMsg = "Войдите под своими учетными данными";
                die(json_encode($result));
            }
            
            $admin_response = new UserMessage();
            $admin_response->from_user_id = $auth['id'];
            $admin_response->msg = $msg;
            $admin_response->item_datetime = new RawValue('default');
            $admin_response->to_user_id = $question->from_user_id;
            $admin_response->is_new = true;
            $admin_response->category_id = $category->id;
            $admin_response->msg_subject = 'Re: ' . $question->msg_subject;
            
            if (!$admin_response->save()) {
                $result->hasError = true;
                $result->errorMsg = "Сообщение не отправлено: " . PHP_EOL;
                foreach ($admin_response->getMessages() as $errMessage) {
                    $result->errorMsg += $errMessage . PHP_EOL;
                }
                die(json_encode($result));
            }
            
            $question->is_new = false;
            $question->save();
            
            $result->hasError = false;
            die(json_encode($result));
        }
        $result->hasError = true;
        $result->errorMsg = "Не верные параметры";
        die(json_encode($result));
    }

}
