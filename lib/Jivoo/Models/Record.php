<?php
// Jivoo
// Copyright (c) 2015 Niels Sonnich Poulsen (http://nielssp.dk)
// Licensed under the MIT license.
// See the LICENSE file or http://opensource.org/licenses/MIT for more information.
namespace Jivoo\Models;

/**
 * A record associated with a {@see IModel}.
 */
class Record implements IRecord {
  /**
   * @var array Associative array of record data.
   */
  private $data = array();
  
  /**
   * @var array Associative array of unsaved data.
   */
  private $updatedData = array();
  
  /**
   * @var IModel Model.
   */
  private $model;
  
  /**
   * @var string[] Associative array of field names and error messages.
   */
  private $errors = array();
  
  /**
   * @var bool Is new.
   */
  private $new = false;
  
  /**
   * @var bool Is saved.
   */
  private $saved = true;
  
  /**
   * Construct record.
   * @param IModel $model Associated model.
   * @param array $data Associative array of record data. 
   * @param string $allowedFields List of allowed fields.
   */
  private function __construct(IModel $model, $data = array(), $allowedFields = null) {
    $this->model = $model;
    $this->data = array_fill_keys($model->getFields(), null);
    $this->addData($data, $allowedFields);
  }
  
  /**
   * Create a new record.
   * @param IModel $model Associated model.
   * @param array $data Associative array of record data.
   * @param string $allowedFields List of allowed fields.
   * @return Record A new record.
   */
  public static function createNew(IModel $model, $data = array(), $allowedFields = null) {
    $record = new Record($model, $data, $allowedFields);
    $record->new = true;
    $record->saved = false;
    return $record;
  }
  
  /**
   * Recreate an existing record.
   * @param IModel $model Associated model.
   * @param array $data Associative array of record data.
   * @return Record An existing record.
   */
  public static function createExisting(IModel $model, $data = array()) {
    $record = new Record($model, $data);
    $record->updatedData = array();
    return $record;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getModel() {
    return $this->model;
  }

  /**
   * {@inheritdoc}
   */
  public function addData($data, $allowedFields = null) {
    if (!is_array($data)) {
      return;
    }
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

  /**
   * {@inheritdoc}
   */
  public function getData() {
    return $this->data;
  }

  /**
   * {@inheritdoc}
   */
  public function __get($field) {
    if (!array_key_exists($field, $this->data))
      throw new InvalidPropertyException(tr('Invalid property: %1', $field));
    return $this->data[$field];
  }

  /**
   * {@inheritdoc}
   */
  public function __set($field, $value) {
    if (!array_key_exists($field, $this->data))
      throw new InvalidPropertyException(tr('Invalid property: %1', $field));
    $this->data[$field] = $value;
    $this->updatedData[$field] = $value;
    $this->saved = false;
  }

  /**
   * {@inheritdoc}
   */
  public function __isset($field) {
    if (!array_key_exists($field, $this->data))
      throw new InvalidPropertyException(tr('Invalid property: %1', $field));
    return isset($this->data[$field]);
  }

  /**
   * {@inheritdoc}
   */
  public function __unset($field) {
    if (!array_key_exists($field, $this->data))
      throw new InvalidPropertyException(tr('Invalid property: %1', $field));
    $this->data[$field] = null;
    $this->updatedData[$field] = null;
    $this->saved = false;
  }

  /**
   * {@inheritdoc}
   */
  public function set($field, $value) {
    $this->__set($field, $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isSaved() {
    return $this->saved;
  }

  /**
   * {@inheritdoc}
   */
  public function isNew() {
    return $this->new;
  }

  /**
   * {@inheritdoc}
   */
  public function getErrors() {
    return $this->errors;
  }

  /**
   * {@inheritdoc}
   */
  public function isValid() {
    $validator = $this->model->getValidator();
    if (!isset($validator))
      return true;
    $this->errors = $validator->validate($this);
    return count($this->errors) == 0;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
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
    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $this->model->selectRecord($this)->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function offsetExists($field) {
    return $this->__isset($field);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetGet($field) {
    return $this->__get($field);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet($field, $value) {
    $this->__set($field, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset($field) {
    $this->__unset($field);
  }
}
