<?php
// Module
// Name           : Helpers
// Description    : For helpers
// Author         : apakoh.dk

/**
 * Helpers module. All helpers added to the module, can be accessed as
 * read-only properties.
 * @package Jivoo\Helpers
 */
class Helpers extends LoadableModule {
  
  protected $events = array('beforeLoadHelper', 'afterLoadHelper');
  
    /**
   * @var array Associative array loaded helpers (name => object)
   */
  private $helpers = array();

  
  protected function init() {
    Lib::addIncludePath($this->p('helpers', ''));
  }
  
  /**
   * Get a helper instance
   * @param string $name Helper name
   * @return Helper A helper object
   */
  public function getHelper($name) {
    if (!isset($this->helpers[$name])) {
      $class = $name . 'Helper';
      $this->triggerEvent('beforeLoadHelper', new LoadHelperEvent($this, $name));
      Lib::assumeSubclassOf($class, 'Helper');
      $this->helpers[$name] = new $class($this->app);
      $this->triggerEvent('afterLoadHelper', new LoadHelperEvent($this, $name, $this->helpers[$name]));
    }
    return $this->helpers[$name];
  }

  
  public function getHelpers($names) {
    $helpers = array();
    foreach ($names as $name)
      $helpers[$name] = $this->getHelper($name);
    return $helpers;
  }
  
  public function hasHelper($name) {
    return isset($this->helpers[$name]);
  }
  
  /**
   * Get a helper instance
   * @param string $name Helper name
   * @return Helper|null A helper object or null on failure
   */
  public function __get($name) {
    return $this->getHelper($name);
  }
  
  public function __isset($name) {
    return $this->hasHelper($name);
  }
}

/**
 * Event sent before and after a helper has been loaded
 */
class LoadHelperEvent extends LoadEvent { }
