<?php
// Jivoo
// Copyright (c) 2015 Niels Sonnich Poulsen (http://nielssp.dk)
// Licensed under the MIT license.
// See the LICENSE file or http://opensource.org/licenses/MIT for more information.
namespace Jivoo\Core;

use Jivoo\InvalidPropertyException;
use Jivoo\InvalidMethodException;

/**
 * Loads and keeps track of Jivoo modules (subclasses of {@see LoadableModule}).
 * @property-read \Jivoo\AccessControl\AccessControl $AccessControl
 * @property-read \Jivoo\ActiveModels\ActiveModels $ActiveModels
 * @property-read \Jivoo\Assets\Assets $Assets
 * @property-read \Jivoo\Console\Console $Console
 * @property-read \Jivoo\Content\Content $Content
 * @property-read \Jivoo\Controllers\Controllers $Controllers
 * @property-read \Jivoo\Databases\Databases $Databases
 * @property-read \Jivoo\Extensions\Extensions $Extensions
 * @property-read \Jivoo\Helpers\Helpers $Helpers
 * @property-read \Jivoo\Jtk\Jtk $Jtk
 * @property-read \Jivoo\Migrations\Migrations $Migrations
 * @property-read \Jivoo\Models\Models $Models
 * @property-read \Jivoo\Routing\Routing $Routing
 * @property-read \Jivoo\Setup\Setup $Setup
 * @property-read \Jivoo\Snippets\Snippets $Snippets
 * @property-read \Jivoo\Themes\Themes $Themes
 * @property-read \Jivoo\View\View $View
 */
class ModuleLoader extends EventSubjectBase {
  /**
   * @var LoadableModule[]
   */
  private $objects = array();

  /**
   * @var ObjectMacro[]
   */
  private $lazyModules = array();
  
  /**
   * @var string[]
   */
  private $classes = array();
  
  /**
   * @var string[][]
   */
  private $before = array();
  
  /**
   * @var string[][]
   */
  private $after = array();
  
  /**
   * @var mixed[]
   */
  private $properties = array();
  
  /**
   * @var callable[]
   */
  private $methods = array();
  
  /**
   * {@inheritdoc}
   */
  protected $events = array('moduleLoad', 'moduleLoaded');
  
  /**
   * Get a loaded module.
   * @param string $module Module name.
   * @return LoadableModule Module.
   */
  public function __get($module) {
    if (isset($this->objects[$module]))
      return $this->objects[$module];
    throw new InvalidModuleException('Module not loaded: ' . $module);
  }
  
  /**
   * Whether a module has been loaded.
   * @param string $module Module name.
   * @return bool True if module is loaded.
   */
  public function __isset($module) {
    return isset($this->objects[$module]);
  }
  
  /**
   * Load a module.
   * @param string $module Module name.
   * @param object $object Module.
   */
  public function __set($module, $object) {
    $this->triggerEvent('moduleLoad', new LoadModuleEvent($this, $module));
    $this->objects[$module] = $object;
    $this->triggerEvent('moduleLoaded', new LoadModuleEvent($this, $module, $object));
    if (isset($this->lazyModules[$module]))
      $this->lazyModules[$module]->playMacro($object, false);
  }
  
  /**
   * Add a read-only property to all modules.
   * @param string $property Property.
   * @param mixed $value Value.
   */
  public function addProperty($property, $value) {
    $this->properties[$property] = $value;
  }
  
  public function removeProperty($property) {
    if (isset($this->properties[$property]))
      unset($this->properties[$property]);
  }
  
  public function getProperty($property) {
    if (array_key_exists($property, $this->properties))
      return $this->properties[$property];
    throw new InvalidPropertyException(tr('Invalid property: %1', $property));
  }
  
  public function hasProperty($property) {
    return array_key_exists($property, $this->properties);
  }
  
  public function addMethod($method, $callable) {
    $this->methods[$method] = $callable;
  }
  
  public function removeMethod($method) {
    if (isset($this->methods[$method]))
      unset($this->methods[$method]);
  }
  
  public function callMethod($method, $parameters) {
    if (array_key_exists($method, $this->methods))
      return call_user_func_array($this->methods[$method], $parameters);
    throw new InvalidMethodException(tr('Invalid method: %1', $method));
  }
  
  /**
   * Get a lazy instance of a module if the module has not already been loaded.
   * The lazy instance will record uses of setters and methods.
   * @param string $module Module name.
   * @return ObjectMacro|LoadableModule A lazy module (see {@see ObjectMacro})
   * or a module if already loaded.
   */
  public function lazy($module) {
    if (isset($this->objects[$module]))
      return $this->objects[$module];
    if (!isset($this->lazyModules[$module]))
      $this->lazyModules[$module] = new ObjectMacro();
    return $this->lazyModules[$module];
  }
}

/**
 * Event sent before and after a module has been loaded
 */
class LoadModuleEvent extends LoadEvent { }