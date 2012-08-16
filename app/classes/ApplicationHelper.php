<?php

abstract class ApplicationHelper {

  
  protected $m = NULL;
  protected $request = NULL;
  protected $session = NULL;

  protected $controller = NULL;

  protected $helpers = array();

  private $helperObjects = array();

  public final function __construct(Templates $templates, Routes $routes, $controller = NULL) {
    $this->m = new Dictionary();
    $this->m->Templates = $templates;
    $this->m->Routes = $routes;
    
    $this->request = $routes->getRequest();
    $this->session = $this->request->session;

    $this->controller = $controller;
    
    foreach ($this->helpers as $name) {
      $class = $name . 'Helper';
      if (class_exists($class)) {
        $this->helperObjects[$name] = new $class($templates, $routes, $this);
      }
    }

    $this->init();
  }

  public function __get($name) {
    if (isset($this->helperObjects[$name])) {
      return $this->helperObjects[$name];
    }
  }

  protected function init() {
  }
  
  protected function getLink($route) {
    return $this->m->Routes->getLink($route);
  }

}
