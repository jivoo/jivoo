<?php

class AdminController extends AppController {
  
  protected $helpers = array('Html', 'Form', 'Admin');

  public function init() {
    parent::init();
    $this->Auth->permissionPrefix = 'admin.';
    $this->Auth->allow('admin.Admin.login');
    if ($this->Auth->isLoggedIn())
      $this->Auth->allow('admin.Admin.accessDenied');
  }
  
  public function before() {
    $this->Admin->importDefaultTheme();
  }
  
  public function index() {
    if ($this->Auth->isLoggedIn()) {
      return $this->redirect('dashboard');
    }
    return $this->redirect('login');
  }
  
  public function dashboard() {
    $this->title = tr('Dashboard');
    return $this->render();
  }

  public function about() {
    $this->title = tr('About');
    return $this->render();
  }

  public function accessDenied() {
    $this->title = tr('Access Denied');
    return $this->render();
  }

  public function logout() {
    $this->Auth->logOut();
    $this->goBack();
    return $this->refresh();
  }

  public function login() {
    if ($this->Auth->isLoggedIn())
      return $this->redirect('dashboard');
    $this->title = tr('Log in');

    $this->login = new Form('login');

    if ($this->request->isPost()) {
      if ($this->Auth->logIn()) {
        $this->goBack();
        return $this->refresh();
      }
      else {
        $this->session->flash['error'][] = tr('Incorret username and/or password.');
      }
    }
    return $this->render();
  }
}
