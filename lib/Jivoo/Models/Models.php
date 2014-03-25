<?php
// Module
// Name           : Models
// Description    : Apakoh Core model system
// Author         : apakoh.dk

Lib::import('Core/Models/Validation');
Lib::import('Core/Models/Selection');
Lib::import('Core/Models/Condition');
Lib::import('Core/Models/Encoding');

/**
 * Models module, finds all models in application
 * @package Core\Models
 */
class Models extends ModuleBase implements IDictionary {
  /**
   * @var string[] List of model class names
   */
  private $modelClasses = array();
  
  /**
   * @var array Associative array of model names and objects
   */
  private $modelObjects = array();
  
  protected function init() {
    $modelsDir = $this->p('models', '');
    if (is_dir($modelsDir)) {
      Lib::addIncludePath($modelsDir);
      $dir = opendir($modelsDir);
      if ($dir) {
        while ($file = readdir($dir)) {
          $split = explode('.', $file);
          if (isset($split[1]) AND $split[1] == 'php') {
            $this->addClass($split[0]);
          }
        }
        closedir($dir);
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
   * Add models needed by a controller or helper
   * @param Controller|Helper $controller Controller or helper objects
   */
  public function addModels($controller) {
    $models = $controller->getModelList();
    foreach ($models as $name) {
      $model = $this->getModel($name);
      if ($model != null) {
        $controller->addModel($name, $model);
      }
      else {
        throw new ModelNotFoundException(tr(
          'Model "%1" not found for %2', $name, get_class($controller)
        ));
      }
    }
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
  
  /* IDictionary implementation */
  
  public function __get($name) {
      if (isset($this->modelObjects[$name])) {
      return $this->modelObjects[$name];
    }
    throw new ModelNotfoundException(tr('Model "%1" not found', $name));
  }
  
  public function __set($name, $model) {
    $this->setModel($name, $model);
  }
  
  public function __isset($name) {
    return isset($this->modelObjects[$name]);
  }
  
  public function __unset($name) {
    unset($this->modelObjects[$name]);
  }
  
  public function isReadOnly() {
    return false;
  }
}

class ModelNotFoundException extends Exception { }
