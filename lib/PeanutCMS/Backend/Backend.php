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

  private $submenus = array();

  protected function init() {
    $this->config->defaults = array(
      'path' => 'admin',
    );

    $path = $this->config['path'];

    $this->m->Controllers->setControllerPath('Backend', $path);

    $this->m->Routing->autoRoute('Backend');

    $this['peanutcms']->setup('PeanutCMS', -2)
      ->item(tr('Home'), null, 0) 
      ->item(tr('Dashboard'), 'Backend::dashboard', 0)
      ->item(tr('About'), 'Backend::about', 8)
      ->item(tr('Log out'), 'Backend::logout', 10);
    
    $this['settings']->setup(tr('Settings'), 10)
      ->item(tr('Themes'), null, 2)
      ->item(tr('Modules'), null, 2)
      ->item(tr('Configuration'), 'Backend::configuration', 10);

    $this->view
      ->setTemplateVar('backend/layout.html', 'aboutLink',
        $this->m->Routing
          ->getLink(
            array('controller' => 'Backend', 'action' => 'about')
          )
    );

    $this->m->Routing->onRendering(array($this, 'prepareMenu'));
  }

  public function getRoute() {
    return array(
      'path' => explode('/', $this->config['path'])
    );
  }
  
  public function prepareMenu($sender, $eventArgs) {
    if (!$this->m->Authentication->hasPermission('backend.access')) {
      $this->view->setTemplateVar('backend/layout.html', 'menu', array());
      return array();
    }
    $menu = array();
    foreach ($this->submenus as $submenu) {
      if ($submenu->prepare()) {
        $menu[] = $submenu;
      }
    }
    Utilities::groupObjects($menu);
    $this->view->setTemplateVar('backend/layout.html', 'menu', $menu);
  }

  public function offsetExists($submenu) {
    return isset($this->submenus[$submenu]);
  }

  public function offsetGet($submenu) {
    if (!isset($this->submenus[$submenu])) {
      $this->submenus[$submenu] = new BackendMenu($submenu);
    }
    return $this->submenus[$submenu];
  }

  public function offsetSet($submenu, $value) {
    // not implemented
  }

  public function offsetUnset($submenu) {
    unset($this->submenus[$submenu]);
  }
}
