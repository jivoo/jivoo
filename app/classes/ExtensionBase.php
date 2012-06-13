<?php

abstract class ExtensionBase {

  private $extensionDir;
  
  protected $m = NULL;
  
  protected $config = NULL;
  
  public final function __construct($modules, Configuration $config) {
    $this->config = $config;
    $this->m = new Dictionary($modules, TRUE);
    $this->extensionDir = get_class($this);
    $this->init();
  }

  protected function getLink($file) {
    return w(EXTENSIONS . $this->extensionDir . '/' . $file);
  }
  
  protected abstract function init();

  public function uninstall() {
    // nothing here
  }
}
