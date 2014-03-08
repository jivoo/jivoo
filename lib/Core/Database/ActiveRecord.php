<?php
class ActiveRecord implements IRecord, ILinkable {
  
  private $data = array();
  
  private $virtualData = array();
  
  private $updatedData = array();

  private $getters = array();
  private $setters = array();

  /**
   * @var ActiveModel
   */
  private $model;
  private $errors = array();
  private $new = false;
  private $saved = true;

  private $associations = array();

  private final function __construct(ActiveModel $model, $data = array(), $allowedFields = null) {
    $this->model = $model;
    $this->data = array_fill_keys($model->getNonVirtualFields(), null);
    $this->virtualData = array_fill_keys($model->getVirtualFields(), null);
    $this->addData($data, $allowedFields);
    $this->associations = $this->model->getAssociations();
    $this->getters = $this->model->getGetters();
    $this->setters = $this->model->getSetters();
  }

  public static function createNew(ActiveModel $model, $data = array(), $allowedFields = null, $class = null) {
    if (isset($class))
      $record = new $class($model, $data, $allowedFields);
    else
      $record = new ActiveRecord($model, $data, $allowedFields);
    $record->new = true;
    $record->saved = false;
    $model->afterCreate($record);
    return $record;
  }
  
  public static function createExisting(ActiveModel $model, $data = array(), $class = null) {
    if (isset($class))
      $record = new $class($model, $data);
    else
      $record = new ActiveRecord($model, $data);
    $record->updatedData = array();
    $model->afterLoad($record);
    return $record;
  }

  public function getModel() {
    return $this->model;
  }
  
  public function addData($data, $allowedFields = null) {
    assume(is_array($data));
    if (!isset($allowedFields))
      $allowedFields = $this->model->getFields();
    if (is_array($allowedFields)) {
      $allowedFields = array_flip($allowedFields);
      $data = array_intersect_key($data, $allowedFields);
    }
    foreach ($data as $field => $value) {
      $this->__set($field, $data[$field]);
    }
  }

  public function __get($field) {
    if (isset($this->getters[$field]))
      return call_user_func(array($this->model, $this->getters[$field]), $this);
    if (isset($this->associations[$field])) {
      if (!is_array($this->associations[$field]))
        $this->associations[$field] = $this->model->getAssociation($this, $this->associations[$field]);
      return $this->associations[$field];
    }
    if (array_key_exists($field, $this->data))
      return $this->data[$field];
    if (array_key_exists($field, $this->virtualData))
      return $this->virtualData[$field];
    throw new InvalidRecordFieldException(tr('"%1" is not a valid field', $field));
  }

  public function __set($field, $value) {
    if (isset($this->setters[$field]))
      call_user_func(array($this->model, $this->setters[$field]), $this, $value);
    else if (isset($this->associations[$field])) {
      $this->model->setAssociation($this, $this->associations[$field], $value);
    }
    else if (array_key_exists($field, $this->data)) {
      $value = $this->model->getType($field)->convert($value);
      $this->data[$field] = $value;
      $this->updatedData[$field] = $value;
      $this->saved = false;
    }
    else if (array_key_exists($field, $this->virtualData))
      $this->virtualData[$field] = $value;
    else
      throw new InvalidRecordFieldException(tr('"%1" is not a valid field', $field));
  }

  public function __isset($field) {
    if (isset($this->getters[$field])) {
      $value = call_user_func(array($this->model, $this->getters[$field]), $this);
      return isset($value);
    }
    if (isset($this->associations[$field]))
      return $this->model->hasAssociation($this, $this->associations[$field]);
    if (array_key_exists($field, $this->data))
      return isset($this->data[$field]);
    if (array_key_exists($field, $this->virtualData))
      return isset($this->virtualData[$field]);
    throw new InvalidRecordFieldException(tr('"%1" is not a valid field', $field));
  }

  public function __unset($field) {
    if (isset($this->setters[$field]))
      call_user_func(array($this->model, $this->setters[$field]), $this, null);
    else if (isset($this->associations[$field]))
      $this->model->unsetAssociation($this, $this->associations[$field]);
    else if (array_key_exists($field, $this->data)) {
      $this->data[$field] = null;
      $this->updatedData[$field] = null;
      $this->saved = false;
    }
    else if (array_key_exists($field, $this->virtualData)) {
      $this->virtualData[$field] = null;
    }
    else
      throw new InvalidRecordFieldException(tr('"%1" is not a valid field', $field));
  }

  public function __call($method, $parameters) {
    $method = 'record' . ucfirst($method);
    $function = array($this->model, $method);
    array_unshift($parameters, $this);
    if (function_exists($function))
      return call_user_func_array($function, $parameters);
    throw new InvalidMethodException(tr('"%1" is not a valid method', $method));
  }

  public function set($field, $value) {
    $this->__set($field, $value);
    return $this;
  }
  
  public function isChanged($field) {
    return array_key_exists($field, $this->updatedData);
  }
  
  public function isSaved() {
    return $this->saved;
  }
  
  public function isNew() {
    return $this->new;
  }
  
  public function getErrors() {
    return $this->errors;
  }
  
  public function isValid() {
    $this->model->beforeValidate($this);
    $validator = $this->model->getValidator();
    $this->errors = $validator->validate($this);
    $this->model->afterValidate($this);
    return count($this->errors) == 0;
  }

  public function getRoute() {
    $this->model->getRoute($this);
  }

  public function action($action) {
    $this->model->getAction($this, $action);
  }
  
  public function save() {
    $this->model->beforeSave($this);
    if (!$this->isValid())
      return false;
    if ($this->isNew()) {
      $insertId = $this->model->insert($this->data);
      $pk = $this->model->getAiPrimaryKey();
      if (isset($pk))
        $this->data[$pk] = $insertId;
      $this->new = false;
    }
    else if (count($this->updatedData) > 0) {
      $this->model->selectRecord($this)->set($this->updatedData)->update();
    }
    $this->updatedData = array();
    $this->saved = true;
    $this->model->afterSave($this);
    return true;
  }
  
  public function delete() {
    $this->model->beforeDelete($this);
    $this->model->selectRecord($this)->delete();
  }

}

class InvalidMethodException extends Exception { }
