<?php
// Module
// Name           : Helpers
// Version        : 0.3.14
// Description    : For helpers
// Author         : apakoh.dk
// Dependencies   : Core/Routing Core/Templates

/**
 * Helpers module
 * 
 * @package Core
 * @subpackage Helpers
 */
class Helpers extends ModuleBase {
  
  private $helperObjects = array();
  private $helpers = array();
  
  protected function init() {
    $dir = opendir($this->p('helpers', ''));
    while ($file = readdir($dir)) {
      $split = explode('.', $file);
      if (isset($split[1]) AND $split[1] == 'php') {
        $class = $split[0];
        $name = str_replace('Helper', '', $class);
        $this->helpers[$name] = $class;
      }
    }
  }
  
  private function getInstance($name) {
    if (isset($this->helpers[$name])) {
      if (!isset($this->helperObjects[$name])) {
        $class = $this->helpers[$name];
        $this->helperObjects[$name] = new $class($this->m->Routing, $this->app->config);
        $helper = $this->helperObjects[$name];

        $modules = $helper->getModuleList();
        foreach ($modules as $moduleName) {
          $module = $this->app->requestModule($moduleName);
          if ($module) {
            $helper->addModule($module);
          }
          else {
            Logger::error(tr('Module "%1" not found in helper %2', $moduleName, $name));
          }
        }
        $helpers = $helper->getHelperList();
        foreach ($helpers as $helperName) {
          $helper = $this->getHelper($helperName);
          if ($helper != null) {
            $helper->addHelper($helper);
          }
          else {
            Logger::error(tr('Helper "%1" not found in helper %2', $helperName, $name));
          }
        }
      }
      return $this->helperObjects[$name];
    }
    return null;
  }
  
  public function addHelper(Helper $helper) {
    $name = str_replace('Helper', '', get_class($helper));
    $this->helperObjects[$name] = $helper;
  }
  
  public function getHelper($name) {
    if (isset($this->helperObjects[$name])) {
      return $this->helperObjects[$name];
    }
    return $this->getInstance($name);
  }
  
  public function __get($name) {
    return $this->getHelper($name);
  }
}