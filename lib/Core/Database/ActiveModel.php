<?php

abstract class ActiveModel extends Model {

  protected $table = null;

  protected $record = null;

  protected $hasMany = array();

  protected $hasAndBelongsToMany = array();

  protected $belongsTo = array();

  protected $hasOne = array();

  protected $validate = array();

  protected $labels = array();

  protected $mixins = array();
  
  protected $virtual = array();

  /**
   * @var Table
   */
  private $source;
  /**
   * @var IDatabase
   */
  private $database;
  
  private $name;
  
  private $mixinObjects = array();
  
  /**
   * @var Schema
   */
  private $schema;
  
  private $fields = array();
  private $nonVirtualFields = array();
  private $virtualFields = array();
  
  private $validator;

  private $associations = null;
  private $aiPrmaryKey = null;
  
  public final function __construct(IDatabase $database) {
    $this->database = $database;
    $this->name = preg_replace('/Model$/', '', get_class($this));
    if (!isset($this->table))
      $this->table = $this->name;
    $table = $this->table;
    if (!$this->database->tableExists($table))
      throw new TableNotFoundException(tr(
        'Table "%1" not found in model %2', $table, $this->name
      ));
    $this->source = $this->database->$table;

    $this->schema = $this->source->getSchema();
    $pk = $this->schema->getPrimaryKey();
    if (count($pk) == 1) {
      $pk = $pk[0];
      $type = $this->schema->$pk;
      if ($type->isInteger() and $type->autoIncrement)
        $this->aiPrimaryKey = $pk;
    }
    
    $this->nonVirtualFields = $this->schema->getFields();
    $this->fields = $this->nonVirtualFields;
    foreach ($this->virtuals as $field => $options) {
      $this->fields[] = $field;
      $this->virtualFields[] = $field;
    }

    $this->validator = new Validator($this, $this->validate);
    if (isset($this->record)) {
      if (!class_exists($this->record) or !is_subclass_of($this->record, 'ActiveRecord'))
        throw new InvalidRecordClassException(tr(
          'Record class %1 must exist and extend %2', $this->record, 'ActiveRecord'
        ));
    }
    
    foreach ($this->mixins as $mixin => $options) {
      if (!is_string($mixin)) {
        $mixin = $options;
        $options = null;
      }
      $mixin .= 'Mixin';
      if (!Lib::classExists($mixin))
        throw new ClassNotFoundException(tr('Mixin class not found: %1', $mixin));
      if (!is_subclass_of($mixin, 'ActiveModelMixin'))
        throw new InvalidMixinException(tr('Mixin class %1 must extend ActiveModelMixin', $mixin));
      $this->mixinObjects[] = new $mixin($this, $options);
    }
  }

  public function create($data = array(), $allowedFields = null) {
    return ActiveRecord::createNew($this, $data, $allowedFields, $this->record);
  }
  
  public function createExisting($data = array()) {
    return ActiveRecord::createExisting($this, $data, $this->record);
  }

  public function getDatabase() {
    return $this->database;
  }

  public function getAiPrimaryKey() {
    return $this->aiPrimaryKey;
  }
  
  public function getAssociations() {
    if (!isset($this->associations))
      $this->createAssociations();
    return $this->associations;
  }

  private function createAssociations() {
    foreach (array('hasOne', 'belongsTo', 'hasMany', 'hasAndBelongsToMany') as $type) {
      foreach ($this->$type as $name => $options) {
        if (!is_string($name)) {
          if (!is_string($options) or !($type == 'belongsTo' or $type == 'hasOne'))
            throw new InvalidAssociationException(tr(
              'Invalid "%1"-association in %2', $type, $this->name
            ));
          $name = lcfirst($options);
          $options = array(
            'model' => $options
          );
        }
        if (is_string($options)) {
          $options = array(
            'model' => $options
          );
        }
        $this->createAssociation($associationType, $name, $options);
      }
    }
  }

  private function createAssociation($type, $name, $options) {
    $otherModel = $options['model'];
    if (!isset($this->database->$otherModel)) {
      throw new ModelNotFoundException(tr(
        'Model %1 not found in  %2', $otherModel, $this->name
      ));
    }
    $options['model'] = $this->database->$otherModel;
    if (!isset($options['thisKey'])) {
      $options['thisKey'] = lcfirst($this->name) . 'Id';
    }
    if (!isset($options['otherKey'])) {
      $options['otherKey'] = lcfirst($otherClass) . 'Id';
    }
    if ($type == 'hasAndBelongsToMany' AND !isset($options['join'])) {
      if ($options['model'] instanceof ActiveModel) { 
        $otherTable = $options['model']->table;
        if (strcmp($this->table, $otherTable) < 0) {
          $options['join'] = $this->table .  $otherTable;
        }
        else {
          $options['join'] = $otherTable . $this->table;
        }
      }
      else {
        throw new InvalidModelException(tr(
          '%1 invalid for joining with %2, must extend ActivRecord',
          $otherModel, $this->name
        ));
      }
    }
    if (isset($options['join'])) {
      if (!isset($this->database->$options['join'])) {
        throw new DataSourceNotFoundException(tr(
          'Association data source "%1" not found', $options['join']
        ));
      }
      $options['join'] = $this->database->$options['join'];
    }
    $this->associations[$name] = $options;
  }
  
  public function beforeSave(ActiveRecord $record) { }
  public function afterSave(ActiveRecord $record) { }
  
  public function beforeValidate(ActiveRecord $record) { }
  public function afterValidate(ActiveRecord $record) { }
  
  public function afterCreate(ActiveRecord $record) { }
  
  public function beforeDelete(ActiveRecord $record) { }
  
  public function getName() {
    return $this->name;
  }
  
  public function getSchema() {
    return $this->schema;
  }
  
  public function getValidator() {
    return $this->validator;
  }

  public function getFields() {
    return $this->fields;
  }

  public function getVirtualFields() {
    return $this->virtualFields;
  }

  public function getNonVirtualFields() {
    return $this->nonVirtualFields;
  }

  public function getLabel($field) {
    if (isset($this->labels[$field]))
      return $this->labels[$field];
    return ucfirst($field);
  }
  
  public function update(UpdateSelection $selection = null) {
    if (!isset($selection))
      $selection = new UpdateSelection($this);
    return $this->source->update($selection);
  }
  
  public function delete(DeleteSelection $selection = null) {
    if (!isset($selection))
      $selection = new DeleteSelection($this);
    return $this->source->delete($selection);
  }
  
  public function count(ReadSelection $selection = null) {
    if (!isset($selection))
      $selection = new ReadSelection($this);
    return $this->source->count($selection);
  }
  
  public function first(ReadSelection $selection = null) {
    if (!isset($selection))
      $selection = new ReadSelection($this);
    $resultSet = $this->source->readSelection($selection->limit(1));
    if (!$resultSet->hasRows())
      return null;
    return $this->createExisting($resultSet->fetchAssoc());
  }
  
  public function last(ReadSelection $selection = null) {
    if (!isset($selection))
      $selection = new ReadSelection($this);
    $resultSet = $this->source->readSelection($selection->reverseOrder()->limit(1));
    if (!$resultSet->hasRows())
      return null;
    return $this->createExisting($resultSet->fetchAssoc());
  }

  public function read(ReadSelection $selection) {
    $resultSet = $this->source->readSelection($selection);
    return new ResultSetIterator($this, $resultSet);
  }

  public function readCustom(ReadSelection $selection) {
    return $this->source->readCustom($selection);
  }
  
  public function insert($data) {
    return $this->source->insert($data);
  }

  public function getAssociation(ActiveRecord $record, $association) {
  }

  public function hasAssociation(ActiveRecord $record, $association) {
  }

  public function unsetAssociation(ActiveRecord $record, $association) {
  }

  public function setAssociation(ActiveRecord $record, $association, $value) {
  }
}

class InvalidRecordClassException extends Exception { } 

/**
 * A data source was not found
 * @package Core\Database
 */
class DataSourceNotFoundException extends Exception { }
class InvalidAssociationException extends Exception { }
class InvalidMixinException extends Exception { }
