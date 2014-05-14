<?php
/**
 * Extension base class
 * @package PeanutCMS\Extensions
 */
abstract class ExtensionBase {

  private $extensions;

  private $extensionDir;

  protected $m = null;

  protected $e = null;

  protected $config = null;
  
  protected $request = null;
  
  protected $session = null;
  
  protected $view = null;

  public final function __construct($m, $e, AppConfig $config, Extensions $extensions) {
    $this->config = $config;
    $this->extensions = $extensions;
    $this->m = new Map($m, true);
    $this->e = new Map($e, true);
    $this->extensionDir = get_class($this);
    
    if (isset($this->m->Routing)) {
      $this->request = $this->m->Routing->getRequest();
      $this->session = $this->request->session;
    }
    
    if (isset($this->m->Templates)) {
      $this->view = $this->m->Templates->view;
    }
    
    $this->init();
  }

  protected function load($className) {
    $fileName = $className . '.php';
    if (file_exists($this->p($fileName))) {
      include($this->p($fileName));
    }
    else if (file_exists($this->p('classes/' . $fileName))) {
      include($this->p('classes/' . $fileName));
    }
    else if (file_exists($this->p('helpers/' . $fileName))) {
      include($this->p('helpers/' . $fileName));
    }
    else if (file_exists($this->p('controllers/' . $fileName))) {
      include($this->p('controllers/' . $fileName));
    }
    else if (file_exists($this->getpath('modules/' . $fileName))) {
      include($this->p('modules/' . $fileName));
    }
  }

  /**
   * Get the absolute path of a file
   * @param string $key Location-identifier
   * @param string $path File
   * @return string Absolute path
   */
  public function p($key, $path = null) {
    if (isset($path)) {
      return $this->extensions
        ->p($key, $path);
    }
    else {
      return $this->extensions
        ->p('extensions', $this->extensionDir . '/' . $key);
    }
  }

  /**
   * Get the absolute path of a file relative to the public directory
   * @param string $path File
   * @return string Path
   */
  public function w($path = '') {
    return $this->extensions
      ->w($path);
  }

  public function getAsset($file) {
    if (!isset($this->m
      ->Assets)) {
      return false;
    }
    return $this->m
      ->Assets
      ->getAsset('extensions', $this->extensionDir . '/' . $file);
  }

  protected abstract function init();

  public function uninstall() {
    // nothing here
  }
}
