    <?php

class SignupController extends ControllerBase {
    
    protected function initialize() {
        parent::initialize();
    }

    public function indexAction() {
        $auth = $this->session->get('auth');
        if (isset($auth['id']) && !$this->request->hasPost('edit_user')) {
            $this->flashSession->success('Добро пожаловать ' . ' [' . $auth['name'] .']');
            return $this->response->redirect('/');
        }
        if ($this->request->hasPost('edit_user')) {
            $this->flashSession->success('Оставьте поле пароля пустым, если не хотите его менять');
        }
    }
    
    private function showAction($user_id) {
        
        $user = User::findFirst($user_id);
        $this->tag->setDefault('uid', $user->id);
        $this->tag->setDefault('login', $user->login);
        $this->tag->setDefault('full_name', $user->full_name);
        $this->tag->setDefault('phone', $user->phone);
        $this->tag->setDefault('location', $user->location);
        $this->tag->setDefault('email', $user->email);
        
        $this->tag->setDefault('operation', 'update');
                
        return $this->dispatcher->forward(array(
                    'controller' => 'signup',
                    'action' => 'register',
        ));
    }
    
    public function editAction() {
        if ($this->request->isPost() && $this->request->hasPost('uid')) {
            $edit_action = $this->request->hasPost('edit_user');
            
            $id = $this->request->getPost('uid', 'int');
            $user = User::find($id);
            if ($user == false) {
                $this->flash->error('Ошибка: нет такого пользователя');
            } elseif ($edit_action) {
                $this->view->user = $user;
                $this->view->edit_user = true;
                return $this->showAction($id);
            }
            
        } else {
            $this->flash->error('Ошибка: неверные параметры - неверный идентификатор');
        }
        return $this->dispatcher->forward(array(
                    'controller' => 'signup',
                    'action' => 'list'
        ));
    }

    public function listAction() {
        $this->view->users = User::find();
    }

    public function loginAction() {
        $auth = $this->session->get('auth');
        if (isset($auth['id'])) {
                return $this->dispatcher->forward(array(
                            'controller' => 'home',
                            'action' => 'index'
                ));
        }
    }
    
    public function logoutAction() {
        $this->session->remove('auth');
        return $this->response->redirect('/');
    }

    public function startAction() {
        if ($this->request->isPost()) {
            $users = new User();
            $user_name = trim($this->request->getPost('username', 'string'));
            $user = $users->findFirst(array(
                "conditions" => "login = ?1",
                "bind" => array(1 => $user_name)
            ));
            
            if ($this->di->get('config')->staticAdmin->login === $user_name && 
                    $this->di->get('config')->staticAdmin->secret === $this->request->getPost('password', 'string')) {
                $this->session->set('auth', array(
                    'name' => $user_name,
                    'id' => 0,
                ));
                $this->flashSession->success('Добро пожаловать ' . $user_name . ' [root]');
                return $this->response->redirect('/');
            }
            
            if (!$user) {
                $this->flash->error('Неверная пара логин/пароль');
                return $this->dispatcher->forward(array(
                            'controller' => 'signup',
                            'action' => 'login'
                ));
            }
            
            $password_is_correct = $this->security->checkHash($this->request->getPost('password', 'string'), $user->hash);
            if ($user && $password_is_correct) {
                $this->session->set('auth', array(
                    'name' => $user_name,
                    'id' => $user->id
                ));
                $this->flashSession->success('Добро пожаловать ' . $user_name);

                return $this->response->redirect('/');
            } else {
                $this->flash->error('Неверная пара логин/пароль');
                return $this->dispatcher->forward(array(
                            'controller' => 'signup',
                            'action' => 'login'
                ));
            }
        }
    }

    public function registerAction() {
        if ($this->request->isPost()) {
            if ($this->request->hasPost('edit_user')) {
                return $this->dispatcher->forward(array(
                            'controller' => 'signup',
                            'action' => 'index'
                ));
            }
            
            $login = trim($this->request->getPost('login', 'string'));
            $email = trim($this->request->getPost('email', 'string'));
            $password = trim($this->request->getPost('password', 'string'));
            $full_name = trim($this->request->getPost('full_name', 'string'));
            $phone = trim($this->request->getPost('phone', 'string'));
            $confirmation = trim($this->request->getPost('confirmation', 'string'));
            $location = trim($this->request->getPost('location', 'string'));
            $rules_accept = (int)($this->request->hasPost('accept_rules'));
                        
            if ($password != $confirmation) {
                $this->flash->error('Пароль и подтвверждение не соответсвуют друг другу');
                return $this->dispatcher->forward(array(
                            'controller' => 'signup',
                            'action' => 'index'
                ));
            }
            
            if ($location === '') {
                $this->flash->error('Населенный пункт должен быть указан');
                return $this->dispatcher->forward(array(
                            'controller' => 'signup',
                            'action' => 'index'
                ));
            }
            
            if ($this->request->hasPost('operation') && $this->request->getPost('operation') == 'update') {
                $id = $this->request->getPost('uid');
                $user = User::findFirst($id);
                if ($user) {
                    $user->login = $login;
                    $user->email = $email;
                    $user->full_name = $full_name;
                    $user->phone = $phone;
                    $user->location = $location;
                    if ($password !== '') {
                        $user->hash = $this->security->hash($password);
                    }
                    
                    if (!$user->update()) {
                        $this->flash->error("Не удалось обновить данные пользователя");
                    } else {
                        $this->flashSession->success("Данные обновлены");
                        return $this->response->redirect('/profile');
                    }
                } else {
                    $this->flash->error("Не могу найти такого пользователя");
                }
            } else {
                if ($rules_accept < 1) {
                    $this->flash->error('Нужно согласиться с правилами чтобы продолжить регистрацию');
                    return $this->dispatcher->forward(array(
                                'controller' => 'signup',
                                'action' => 'index'
                    ));
                }
                if ($password === '') {
                    $this->flash->error('Пароль не должен быть пустым');
                    return $this->dispatcher->forward(array(
                                'controller' => 'signup',
                                'action' => 'index'
                    ));
                }

                if (strlen($password) < 6) {
                    $this->flash->error('Пароль не должен быть короче 6 символов');
                    return $this->dispatcher->forward(array(
                                'controller' => 'signup',
                                'action' => 'index'
                    ));
                }
                $users = new User();
                $ex_user = $users->findFirst(array(
                    "conditions" => "login = ?1",
                    "bind" => array(1 => $login)
                ));
                if ($ex_user) {
                    $this->flash->success('Пользователь уже существует');
                    return $this->dispatcher->forward(array(
                                'controller' => 'signup',
                                'action' => 'index'
                    ));
                } 
                
                $user = new User();
                $user->login = $login;
                $user->email = $email;
                $user->full_name = $full_name;
                $user->phone = $phone;
                $user->hash = $this->security->hash($password);
                $user->is_alive = 1;
                $user->location = $location;
                
                if ($user->save() == true) {
                    $this->flashSession->success("Теперь вы можете войти со своими логином и паролем");
                    return $this->response->redirect('/signup/login');
                } else {
                    foreach ($user->getMessages() as $message) {
                        $this->flashSession->success($message->getMessage());
                    }
                }
            }
        }
        return $this->dispatcher->forward(array(
                    'controller' => 'signup',
                    'action' => 'index'
        ));
    }

    public function endAction() {
        $this->session->remove('auth');
        $this->flashSession->success('Вы вышли из системы!');
        return $this->response->redirect('/');
    }
    
    public function resetAction() {
        ;
    }
    
    public function send_new_passAction() {
        if ($this->request->isPost() && $this->request->hasPost('email')) {
            $email = $this->request->getPost('email', 'string');
            $user = User::findFirst(array(
                'conditions' => 'email = ?1',
                'bind' => array(
                    1 => $email
                )
            ));
            if (!$user) {
                $this->flashSession->error('Ошибка восстановления пароля - email не найден в системе');
                return $this->response->redirect('/');
            }
            $new_pass = User::generatePassword();
            $msg_body = "Новый пароль Вашей учетной записи на сайте http://www.spnovo.com : " . $new_pass . 
                    "\r\nНе забудьте сменить пароль на странице профиля.";
            $headers = 'From: ' . $this->di->get('config')->mail->from . "\r\n";
            $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
            if (mail($email, "Восстановление пароля", $msg_body, $headers)) {
                $this->flashSession->success('Письмо с новым паролем отправлено на указанный E-mail');
                return $this->response->redirect('/signup/login');
            } else {
                $this->flashSession->error('Письмо не отправлено');
            }
        }
        $this->flashSession->error('<pre>' . print_r($_POST,1) . '</pre>');
        $this->flashSession->error('Ошибка восстановления пароля');
        return $this->response->redirect('/');
    }

}
