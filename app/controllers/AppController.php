<?php

class AppController extends Controller {
  
  protected $helpers = array('Html', 'Menu', 'Auth', 'Widgets', 'Form', 'Format', 'Editor', 'Media');
  
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
}
