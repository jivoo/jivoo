<?php
abstract class Migration {
  
  private $db = null;
  
  private $operations = array();

  private $ignoreExceptions = false;
  
  public final function __construct(IMigratableDatabase $db) {
    $this->db = $db;
  }
  
  protected function __get($table) {
    return $this->db->$table;
  }

  protected function __isset($table) {
    return isset($this->db->table);
  }
  
  protected function createTable(Schema $schema) {
    try {
      $this->db->createTable($schema);
    }
    catch (Exception $e) {
      if (!$this->ignoreExceptions) throw $e;
    }
  }

  protected function dropTable($table) {
    try {
      $this->db->dropTable($table); 
    }
    catch (Exception $e) {
      if (!$this->ignoreExceptions) throw $e;
    }
  }

  protected function addColumn($table, $column, DataType $type) {
    try {
      $this->db->addColumn($table, $column, $type); 
    }
    catch (Exception $e) {
      if (!$this->ignoreExceptions) throw $e;
    }
  }

  protected function deleteColumn($table, $column) {
    try {
      $this->db->deleteColumn($table, $column); 
    }
    catch (Exception $e) {
      if (!$this->ignoreExceptions) throw $e;
    }
  }

  protected function alterColumn($table, $column, DataType $type) {
    try {
      $this->db->alterColumn($table, $column, $type); 
    }
    catch (Exception $e) {
      if (!$this->ignoreExceptions) throw $e;
    }
  }

  protected function renameColumn($table, $column, $newName) {
    try {
      $this->db->renameColumn($table, $column, $newName); 
    }
    catch (Exception $e) {
      if (!$this->ignoreExceptions) throw $e;
    }
  }

  protected function createIndex($table, $index, $options = array()) {
    try {
      $this->db->createIndex($table, $index, $options); 
    }
    catch (Exception $e) {
      if (!$this->ignoreExceptions) throw $e;
    }
  }

  protected function deleteIndex($table, $index) {
    try {
      $this->db->deleteIndex($table, $index); 
    }
    catch (Exception $e) {
      if (!$this->ignoreExceptions) throw $e;
    }
  }

  protected function alterIndex($table, $index, $options = array()) {
    try {
      $this->alterIndex($table, $index, $options); 
    }
    catch (Exception $e) {
      if (!$this->ignoreExceptions) throw $e;
    }
  }
  
  public final function revert() {
    $this->ignoreExceptions = true;
    $this->down();
    $this->ignoreExceptions = false;
  }

  public abstract function up();

  public abstract function down();
  
  //public function up() {
    //$operations = $this->change();
    //foreach ($operations as $operation) {
      //$this->do($operation);
    //}
  //}
  
  //public function down() {
    //$operations = array_reverse($this->change());
    //foreach ($operations as $operation) {
      //$this->undo($operation);
    //}
  //}
  
  protected function change() {
    return array();
  }
}
