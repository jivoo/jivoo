<?php

class SetupAuthenticationController extends SetupController {

  protected $helpers = array('Html', 'Form');
  
  protected $models = array('User', 'Group');

  public function setupRoot() {
    $this->title = tr('Welcome to PeanutCMS');

    if (!isset($this->rootGroup)) {
      $this->rootGroup = $this->Group->first(SelectQuery::create()->where('name = "root"'));
      if (!$this->rootGroup) {
        $this->rootGroup = $this->Group->create();
        $this->rootGroup->name = 'root';
        $this->rootGroup->title = tr('Admin');
        $this->rootGroup->save();
        $this->rootGroup->setPermission('*', true);
      }
    }

    if ($this->request->isPost() AND $this->request->checkToken()) {
      $this->user = $this->User->create($this->request->data['user']);
      $this->user->password = $this->m->Shadow
        ->hash($this->user->password);
      $this->user->confirm_password = $this->m->Shadow
        ->hash($this->user->confirm_password);
      if ($this->user->isValid()) {
        $this->user->setGroup($this->rootGroup);
        $this->user->save();
        $this->config->set('rootCreated', true);
        $this->redirect(null);
      }
    }
    else {
      $this->user = $this->User->create();
      $this->user->username = 'root';
    }

    $this->render();
  }

}
