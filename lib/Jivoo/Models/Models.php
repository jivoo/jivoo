<?php
// Jivoo
// Copyright (c) 2015 Niels Sonnich Poulsen (http://nielssp.dk)
// Licensed under the MIT license.
// See the LICENSE file or http://opensource.org/licenses/MIT for more information.
namespace Jivoo\Models;

use Jivoo\Core\LoadableModule;
use Jivoo\Core\Lib;

/**
 * Models module, finds all models in application.
 */
class Models extends LoadableModule {
  
   /**
   * @var string[] List of model class names
   */
  private $modelClasses = array();
  
  /**
   * @var array Associative array of model names and objects
   */
  private $modelObjects = array();

  /**
   * {@inheritdoc}
   */
  protected function init() {
    $modelsDir = $this->p('app', 'models');
    if (is_dir($modelsDir)) {
      Lib::import($modelsDir, $this->app->n('Models'));
      $files = scandir($modelsDir);
      if ($files !== false) {
        foreach ($files as $file) {
          $split = explode('.', $file);
          if (isset($split[1]) AND $split[1] == 'php') {
            $this->addClass($this->app->n('Models\\' . $split[0]));
          }
        }
      }
    }
  }
  
  /**
   * Get list of model classes
   * @return string[] List of models
   */
  public function getModelClasses() {
    return $this->modelClasses;
  }
  
  /**
   * Add an IModel class
   * @param string $class Class name
   */
  public function addClass($class) {
    $this->modelClasses[$class] = $class;
  }
  
  /**
   * Add/set model
   * @param string $name Model name
   * @param IModel $model Model object
   */
  public function setModel($name, IModel $model) {
    if (isset($this->modelClasses[$name])) {
      unset($this->modelClasses[$name]);
    }
    $this->modelObjects[$name] = $model;
  }
  
  /**
   * Get a model
   * @param string $name Model name
   * @return IModel|null Model object or null if not found
   */
  public function getModel($name) {
    if (isset($this->modelObjects[$name])) {
      return $this->modelObjects[$name];
    }
    return null;
  }
  
  /**
   * Get several models.
   * @param string[] $names List of model names.
   * @return IModel[] List of model objects.
   */
  public function getModels($names) {
    $models = array();
    foreach ($names as $name) {
      $models[$name] = $this->getModel($name);
      if (!isset($models[$name]))
        throw new ModelNotFoundException(tr('Model "%1" not found', $name));
    }
    return $models;
  }

  /**
   * {@inheritdoc}
   */
  public function __get($name) {
    if (isset($this->modelObjects[$name])) {
      return $this->modelObjects[$name];
    }
    throw new ModelNotfoundException(tr('Model "%1" not found', $name));
  }

  /**
   * {@inheritdoc}
   */
  public function __set($name, $model) {
    $this->setModel($name, $model);
  }

  /**
   * {@inheritdoc}
   */
  public function __isset($name) {
    return isset($this->modelObjects[$name]);
  }

  /**
   * {@inheritdoc}
   */
  public function __unset($name) {
    unset($this->modelObjects[$name]);
  }
}

/**
 * A model could not be found.
 */
class ModelNotFoundException extends \Exception { }
