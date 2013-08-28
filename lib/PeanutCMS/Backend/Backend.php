<?php
// Module
// Name           : Backend
// Description    : The PeanutCMS administration system
// Author         : apakoh.dk
// Dependencies   : Core/Database Core/Authentication
//                  Core/Routing Core/Templates Core/Controllers

/**
 * PeanutCMS backend module
 *
 * @package PeanutCMS\Backend
 */
class Backend extends ModuleBase implements ILinkable, arrayaccess {

  private $categories = array();
  private $unlisted = null;

  private $controller;

  private $shortcuts = array();

  private $prefix = '';

  protected function init() {
    $this->config->defaults = array(
      'path' => 'admin',
    );

    $path = $this->config['path'];
    $this->prefix = $path . '/';

    $this->controller = $this->m->Controllers->Backend;

    $this->controller->addRoute($path, 'dashboard');
    $this->controller->addRoute($this->prefix . 'login', 'login');
    $this->controller->addRoute($this->prefix . 'access-denied', 'accessDenied');
    $this->controller->addRoute($this->prefix . 'about', 'about');

    $this['peanutcms']->setup('PeanutCMS', -2);
    $this['peanutcms']['home']->setup(tr('Home'), 0, null);
    $this['peanutcms']['dashboard']->setup(tr('Dashboard'), 0,
        array('path' => explode('/', $path)));
    $this['peanutcms']['about']->setup(tr('About'), 8)
      ->autoRoute($this->controller, 'about');
    $this['peanutcms']['logout']->setup(tr('Log out'), 10)
      ->autoRoute($this->controller, 'logout');

    $this['settings']->setup(tr('Settings'), 10);
    $this['settings']['configuration']->setup(tr('Configuration'), 10)
      ->autoRoute($this->controller, 'configuration');
    $this['settings']['themes']->setup(tr('Themes'), 2);
    $this['settings']['modules']->setup(tr('Modules'), 2);

    if ($this->m
      ->Authentication
      ->hasPermission('backend.access')) {
    }
    $this->view->setTemplateVar(
      'backend/layout.html',
      'aboutLink',
      $this->m->Routing->getLink(array(
        'controller' => 'Backend', 'action' => 'about'
      ))
    );

    $this->m->Routing->onRendering(array($this, 'createMenu'));
  }

  public function __get($property) {
    switch ($property) {
      case 'unlisted':
        if (!isset($this->unlisted)) {
          $this->unlisted = new BackendCategory($this,
            $this->m->Authentication);
        }
        return $this->unlisted;
      case 'prefix':
        return $this->$property;
    }
  }

  public function getRoute() {
    return array(
      'path' => explode('/', $this->config['path'])
    );
  }

  /** @todo In case of overflow; combine remaining categories under one "More"-category */
  /** @todo actually... it should be handled in the theme... */
  public function createMenu($sender, $eventArgs) {
    if (!$this->m
      ->Authentication
      ->hasPermission('backend.access')) {
      $this->view->setTemplateVar('backend/layout.html', 'menu', array());
      return array();
    }
    $menu = array();
    foreach ($this->categories as $category) {
      if ($category->count() > 0) {
        $category->group();
        $menu[] = $category;
      }
    }
    Utilities::groupObjects($menu);
    $this->view->setTemplateVar('backend/layout.html', 'menu', $menu);
    return $menu;
  }

  public function offsetExists($category) {
    return isset($this->categories[$category]);
  }

  public function offsetGet($category) {
    if (!isset($this->categories[$category])) {
      $this->categories[$category] = new BackendCategory($this,
        $this->m
          ->Authentication);
    }
    return $this->categories[$category];
  }

  public function offsetSet($category, $value) {
    // not implemented
  }

  public function offsetUnset($category) {
    unset($this->categories[$category]);
  }
}
