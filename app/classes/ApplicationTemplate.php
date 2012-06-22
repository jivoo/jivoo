<?php

abstract class ApplicationTemplate {

  private $m = NULL;

  private $controller = NULL;

  public $data = array();

  public final function __construct(Templates $templates, Routes $routes, $controller = NULL) {
    $this->m = new Dictionary();
    $this->m->Templates = $templates;
    $this->m->Routes = $routes;

    $this->controller = $controller;
  }

  public function __get($name) {
    return $this->get($name);
  }
  
  public function __set($name, $value) {
    $this->set($name, $value);
  }

  public function get($name) {
    if (isset($this->data[$name])) {
      return $this->data[$name];
    }
    return NULL;
  }

  public function set($name, $value) {
    if (is_array($name)) {
      foreach ($name as $n => $value) {
        $this->set($n, $value);
      }
    }
    else {
      $this->data[$name] = $value;
    }
  }

  protected function link($controller = NULL, $action = 'index', $parameters = array()) {
    return $this->m->Routes->getLink($controller, $action, $parameters);
  }

  protected function file($file) {
    return $this->m->Templates->getFile($file);
  }

  protected function insertScript($id, $file, $dependencies = array()) {
    $this->m->Templates->insertScript($id, $file, $dependencies);
  }

  protected function insertStyle($id, $file, $dependencies = array()) {
    $this->m->Templates->insertStyle($id, $file, $dependencies);
  }

  protected function insertMeta($id, $file, $dependencies = array()) {
    $this->m->Templates->insertMeta($id, $file, $dependencies);
  }

  protected function setIndent($indentation = 0) {
    $this->m->Templates->setHtmlIndent($indentation);
  }

  protected function output($location, $linePrefix = '') {
    $this->m->Templates->outputHtml($location, $linePrefix);
  }

  protected function getTemplate($template) {
    return $this->m->Templates->getTemplate($template);
  }

  protected function getTemplateData($template) {
    return $this->m->Templates->getTemplateData($template);
  }

  public abstract function render($template);

}
