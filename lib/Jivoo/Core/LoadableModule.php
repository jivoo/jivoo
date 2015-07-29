<?php
// Jivoo
// Copyright (c) 2015 Niels Sonnich Poulsen (http://nielssp.dk)
// Licensed under the MIT license.
// See the LICENSE file or http://opensource.org/licenses/MIT for more information.
namespace Jivoo\Core;

/**
 * Subclasses of this class can be loaded by {@see App}.
 */
abstract class LoadableModule extends Module {
  
  /**
   * @var string[] Names of modules that (if they are loaded) must be loaded
   * before this module.
   */
  protected static $loadAfter = array();
  
  /**
   * @var string[] Names of modules that (if they are loaded) must be loaded
   * after this module.
   */
  protected static $loadBefore = array();
  
  /**
   * Construct module.
   * @param App $app Associated application.
   */
  public final function __construct(App $app) {
    parent::__construct($app);
    $name = Lib::getClassName($this);
    $this->config = $this->config[$name];
    $this->init();
  }

  /**
   * Module initialization method.
   */
  protected function init() { }
  
  /**
   * Called after the module has been loaded.
   */
  public function afterLoad() { }

  /**
   * Get the absolute path of a file.
   * If called with a single parameter, then the name of the current module
   * is used as location identifier.
   * @param string $key Location-identifier
   * @param string $path File
   * @return string Absolute path
   */
  public function p($key, $path = null) {
    if (isset($path))
      return parent::p($key, $path);
    return parent::p(get_class($this), $key);
  }
  
  /**
   * Get load order for optional dependencies of module and modify a list of
   * optional dependencies.
   * @param string $class Module class (class that extends {@see LoadableModule}).
   * @return string[][] Associative array with two keys: 'before' is an array of
   * modules that must load before, and 'after' is an array of modules that must
   * load after.
   */
  public static function getLoadOrder($class) {
    $vars = get_class_vars($class);
    return array('before' => $vars['loadBefore'], 'after' => $vars['loadAfter']);
  }
}