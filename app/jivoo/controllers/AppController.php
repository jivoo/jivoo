<?php

class AppController extends Controller {
  
  protected $helpers = array('Html', 'Menu', 'Auth', 'Widgets', 'Form', 'Format', 'Editor');
  
  protected $models = array('User');
  
  protected function init() {
    $this->view->data->site = $this->config['site'];
    $this->view->data->app = $this->app->appConfig;
    $this->Auth->userModel = $this->User;
    $this->Auth->authentication = array('Form');
    $this->Auth->authorization = array('Action');
    $this->Auth->loginRoute = 'Admin::login';
    $this->Auth->unauthorizedRoute = 'Admin::accessDenied';
    $this->Auth->acl = array('Record');
    $this->Auth->allow('frontend');
    $this->Auth->permissionPrefix = 'frontend.';
    if ($this->Auth->isLoggedIn())
      $this->user = $this->Auth->user;
  }
  
  public function notFound() {
    $this->setStatus(404);
    return $this->render();
  }
  
  public function login() {
    if ($this->request->isPost()) {
      if ($this->Auth->logIn()) {
        return $this->refresh();
      }
      else {
        $this->session->alert(tr('Incorret username and/or password.'));
      }
    }
    if (isset($this->request->query['logout'])) {
      $this->Auth->logOut();
      return $this->refresh(array());
    }
    return $this->render();
  }
}
