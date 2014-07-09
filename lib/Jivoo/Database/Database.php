<?php
// Module
// Name           : Database
// Description    : The Jivoo database system
// Author         : apakoh.dk
// Dependencies   : Jivoo/Routing Jivoo/Templates Jivoo/Models Jivoo/Helpers
//                  Jivoo/Controllers Jivoo/Setup

Lib::import('Jivoo/Database/Mixins');

/**
 * Database module
 * @package Jivoo\Database
 */
class Database extends LoadableModule implements IDatabase, ITableRevisionMap {
  
  protected $modules = array('Routing', 'Templates', 'Models', 'Helpers', 'Controllers', 'Setup');
  
  /**
   * @var string Driver name
   */
  private $driver;
  
  /**
   * @var array Associative array of driver info
   */
  private $driverInfo;

  /**
   * @var IDatabase Database connection
   */
  private $connection;
  
  /**
   * @var IModel[]
   */
  private $tables = array();

  /**
   * 
   * @var array Associative array of table names and schemas
   */
  private $schemas = array();
  
  private $revisions = array();
  
  /**
   * @var array Associative array of table names and migration status
   */
  private $migrations = array();

  /* Begin IDatabase implementation */
  public function __get($table) {
    if (isset($this->tables[$table]))
      return $this->tables[$table];
    throw new TableNotFoundException(tr('Table "%1" not found', $table));
  }

  public function __isset($table) {
    if (isset($this->tables[$table]))
      return true;
    if ($this->connection)
      return $this->connection->__isset($table);
    return false;
  }

  public function close() {
    if ($this->connection)
      $this->connection->close();
  }

  public function getTable($table, ISchema $schema) {
    if (!isset($this->tables[$table]))
      $this->tables[$table] = $this->connection->getTable($table, $schema);
    return $this->tables[$table];
  }

  public function tableExists($table) {
    return isset($this->tables[$table]);
  }

  public function migrate(Schema $schema, $force = false) {
    if ($schema->getName() == 'undefined') {
      return false;
    }
    $name = $schema->getName();
    $revision = $schema->getRevision();
    if (!$force and isset($this->config['migration'][$name])) {
      if ($this->config['migration'][$name] == $revision) {
        return 'unchanged';
      }
    }
    if ($this->connection) {
      $actualRevision = $this->getRevision($name);
      if (!$force and $actualRevision == $revision) {
        $this->config['migration'][$name] = $revision;
        return 'unchanged';
      }
      $status = $this->connection->migrate($schema);
      $this->setRevision($name, $revision);
      if ($status == 'new')
        $this->config['installed'][$name] = false;
      return $status;
    }
  }
  
  public function beginTransaction() {
    $this->connection->beginTransaction();
  }
  
  public function commit() {
    $this->connection->commit();
  }
  
  public function rollback() {
    $this->connection->rollback();
  }
  /* End IDatabase implementation */
  
  public function getRevision($table) {
    if (!isset($this->revisions[$table])) {
      $record = $this->TableRevision->find($table);
      if (!$record)
        $this->revisions[$table] = -1;
      else
        $this->revisions[$table] = $record->revision;
    }
    return $this->revisions[$table];
  }
  
  private function setRevision($table, $revision) {
    $record = $this->TableRevision->find($table);
    if (!$record)
      $record = $this->TableRevision->create(array('name' => $table));
    $record->revision = $revision;
    $record->save();
    $this->config['migration'][$table] = $revision;
    $this->revisions[$table] = $revision;
  }

  protected function init() {
    if (!isset($this->config['driver']))
      throw new DatabaseConnectionFailedException(
        tr('Database not configured')
      );
    $drivers = new DatabaseDriversHelper($this->app);
    $this->driver = $this->config['driver'];
    $this->driverInfo = $drivers->checkDriver($this->driver);
    if (!$this->driverInfo OR !$this->driverInfo['isAvailable']) {
      throw new DatabaseConnectionFailedException(
        tr('Database driver unavailable: "%1"', $this->driver)
      );
    }
    foreach ($this->driverInfo['requiredOptions'] as $option) {
      if (!isset($this->config[$option])) {
        throw new DatabaseConnectionFailedException(
          tr('Database option missing: "%1"', $option)
        );
      }
    }
    Lib::import('Jivoo/Database/' . $this->driver);
    try {
      $class = $this->driver . 'Database';
      Lib::assumeSubclassOf($class, 'MigratableDatabase');
      $this->connection = new $class($this->app, $this, $this->config);
    }
    catch (DatabaseConnectionFailedException $exception) {
      throw new DatabaseConnectionFailedException(
        tr('Database connection failed: ' . $exception->getMessage())
      );
    }
    
    $this->revisions['TableRevision'] = 0;
    $this->addSchema(new TableRevisionSchema());

    $schemasDir = $this->p('schemas', '');
    if (is_dir($schemasDir)) {
      Lib::addIncludePath($schemasDir);
      $files = scandir($schemasDir);
      if ($files !== false) {
        foreach ($files as $file) {
          $split = explode('.', $file);
          if (isset($split[1]) AND $split[1] == 'php') {
            $class = $split[0];
            $this->addSchema(new $class());
          }
        }
      }
    }

    $classes = $this->m->Models->getModelClasses();
    foreach ($classes as $class) {
      $this->addActiveModel($class);
    }
    
    $this->m->Routing->attachEventHandler('beforeRender', array($this, 'installModels'));
  }
  
  public function installModels($caller = null, $eventArgs = null) {
    foreach ($this->tables as $name => $table) {
      if ($table instanceof ActiveModel
          and !(isset($this->config['installed'][$name])
            and $this->config['installed'][$name])) {
        $table->triggerEvent('install', new ActiveModelEvent($this));
        $this->config['installed'][$name] = true;
      } 
    }
  }
  
  /**
   * Add a Schema if it has not already been added
   * @param string $name Schema name
   * @param string $file Path to schema file
   * @return boolean True if missing and added, false otherwise
   */
  public function addSchemaIfMissing($name, $file) {
    if ($this->hasSchema($name)) {
      return false;
    }
    include $file;
    $class = $name . 'Schema';
    $this->addSchema(new $class());
    return true;
  }
  
  /**
   * Add an active model if it has not already been added
   * @param string $class Class name of active model
   * @param string $file Path to model class file
   * @return True if missing and added successfully, false otherwise
   */
  public function addActiveModelIfMissing($name, $file) {
    if (isset($this->m->Models->$name)) {
      return false;
    }
    if (!Lib::classExists($name, false)) {
      include $file;
    }
    return $this->addActiveModel($name);
  }
  
  /**
   * Whether or not a schema has been added
   * @param string $name Schema (table) name
   * @return True if schema exists, false otherwise
   */
  public function hasSchema($name) {
    return isset($this->schemas[$name]);
  }
  
  /**
   * Add Schema and run migration if necessary
   * @param Schema $schema Schema
   * @return True if added
   */
  public function addSchema(Schema $schema) {
    $name = $schema->getName();
    $this->schemas[$name] = $schema;
    $this->migrations[$name] = $this->migrate($this->schemas[$name]);
    if (!isset($this->$name) and $this->migrations[$name] == 'unchanged') {
      $this->migrations[$name] = $this->migrate($this->schemas[$name], true);
    }
    $this->tables[$name] = $this->connection->getTable($name, $this->schemas[$name]);
    return true;
  }
  
  /**
   * Add an active model
   * @param string $class Class name of active model
   * @return True if successfull, false if table not found
   */
  public function addActiveModel($class) {
    if (is_subclass_of($class, 'ActiveModel')) {
      $model = new $class($this->app, $this);
      $this->m->Models->setModel($class, $model);
      $this->tables[$class] = $model;
      return true;
    }
    return false;
  }

  /**
   * Check if a table is newly created
   * @param string $table Table name
   * @return boolean True if new, false otherwise
   */
  public function isNew($table) {
    return isset($this->migrations[$table])
      AND $this->migrations[$table] == 'new';
  }
}
