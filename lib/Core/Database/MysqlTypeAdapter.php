<?php

class MysqlTypeAdapter implements IMigrationTypeAdapter {

  private $db;

  public function __construct(SqlDatabase $db) {
    $this->db = $db;
  }

  public function encode(DataType $type, $value) {
  }

  public function decode(DataType $type, $value) {
  }

  /**
   * Convert a schema type to a MySQL type
   * @param DataType $type
   * @return string MySQL type
   */
  public function convertType(DataType $type) {
    $autoIncrement = '';
    switch ($type->type) {
      case DataType::INTEGER:
        if ($type->size == DataType::BIG)
          $column = 'BIGINT';
        else if ($type->size == DataType::SMALL)
          $column = 'SMALLINT';
        else if ($type->size == DataType::TINY)
          $column = 'TINYINT';
        else
          $column = 'INT';
        if ($type->unsigned)
          $column .= ' UNSIGNED';
        if ($type->autoIncrement)
          $autoIncrement = ' AUTO_INCREMENT';
        break;
      case DataType::FLOAT:
        $column = 'DOUBLE';
        break;
      case DataType::STRING:
        $column = 'VARCHAR(' . $type->length . ')';
        break;
      case DataType::BOOLEAN:
        $column = 'TINYINT';
        break;
      case DataType::BINARY:
        $column = 'BLOB';
        break;
      case DataType::DATE:
        $column = 'DATE';
        break;
      case DataType::DATETIME:
        $column = 'DATETIME';
        break;
      case DataType::TEXT:
      default:
        $column = 'TEXT';
        break;
    }
    if ($type->notNull)
      $column .= ' NOT';
    $column .= ' NULL';
    if (isset($type->default))
      $column .= $this->db->escapeQuery(' DEFAULT ?', $type->default);
    return $column . $autoIncrement;
  }

  /**
   * Convert a MySQL type to a DataType
   * @param string $type MySQL type
   * @return array A 3-tuple of type name, length and unsigned
   */
  public function checkType($row, DataType $type) {
    preg_match('/ *([^ (]+) *(\(([0-9]+)\))? *(unsigned) *?/i', $row['Type'], $matches);
    $actualType = strtolower($matches[1]);
    $unsigned = isset($matches[4]);
    $length = isset($matches[3]) ? $matches[3] : 0;
    $null = isset($row['Null']) and $row['Null'] != 'NO';
    if ($null != $type->null)
      return false;
    $default = null;
    if (isset($row['Default']))
      $default = $row['Default'];
    if ($default != $type->default)
      return false;
    switch ($type->type) {
      case DataType::INTEGER:
        if ($type->size == DataType::BIG and $actualType != 'bigint')
          return false;
        else if ($type->size == DataType::SMALL and $actualType != 'smallint')
          return false;
        else if ($type->size == DataType::TINY and $actualType != 'tinyint')
          return false;
        else if ($actualType != 'int')
          return false;
        if ($type->unsigned != $unsigned)
          return false;
        if ($type->autoIncrement != $autoIncrement)
          return false;
        break;
      case DataType::FLOAT:
        if ($actualType != 'double')
          return false;
        break;
      case DataType::STRING:
        if ($actualType != 'varchar')
          return false;
        if ($type->length != $length)
          return false;
        break;
      case DataType::BOOLEAN:
        if ($actualType != 'tinyint')
          return false;
        break;
      case DataType::BINARY:
        if ($actualType != 'blob')
          return false;
        break;
      case DataType::DATE:
        if ($actualType != 'date')
          return false;
        break;
      case DataType::DATETIME:
        if ($actualType != 'datetime')
          return false;
        break;
      case DataType::TEXT:
      default:
        if ($actualType != 'text')
          return false;
        break;
    }
    return true;
  }

  public function checkSchema($table, ISchema $schema) {
    $result = $this->db->rawQuery('SHOW COLUMNS FROM ' . $this->db->tableName($table));
    $columns = array();
    while ($row = $result->fetchAssoc()) {
      $column = $row['Field'];
      if (isset($schema->$column))
        $columns[$column] = $this->checkType($row, $schema->$column) ? 'ok' : 'alter';
      else
        $columns[$column] = 'delete';
    }
    foreach ($schema->getFields() as $field) {
      if (!isset($columns[$field]))
        $columns[$field] = 'add';
    }
    $result = $this->db->rawQuery('SHOW INDEX FROM ' . $this->db->tableName($table));
    $actualIndexes = array();
    while ($row = $result->fetchAssoc()) {
      $index = $row['Key_name'];
      $column = $row['Column_name'];
      $unique = $row['Non_unique'] == 0 ? true : false;
      if (isset($actualIndexes[$index]))
        $actualIndexes[$index]['columns'][] = $column;
      else
        $actualIndexes[$index] = array(
          'columns' => array($column),
          'unique' => $unique
        );
    }
    $expectedIndexes = $schema->getIndexes();
    $allIndexes = array_unique(array_merge(
      array_keys($actualIndexes), array_keys($expectedIndexes)
    ));
    $indexes = array();
    foreach ($allIndexes as $index) {
      if (!isset($actualIndexes[$index]))
        $indexes[$index] = 'add';
      else if (!isset($expectedIndexes[$index]))
        $indexes[$index] = 'delete';
      else if ($actualIndexes[$index] != $expectedIndexes[$index])
        $indexes[$index] = 'alter';
      else
        $indexes[$index] = 'ok';
    }
    return array(
      'columns' => $columns,
      'indexes' => $indexes
    );
  }

  public function tableExists($table) {
    $result = $this->db->rawQuery(
      'SHOW TABLES LIKE "' . $this->db->tableName($table) . '"');
    return $result->hasRows();
  }

  public function createTable(Schema $schema) {
    $sql = 'CREATE TABLE ' . $this->db->tableName($schema->getName()) . '(';
    $columns = $schema->getColumns();
    $first = true;
    foreach ($columns as $column) {
      $type = $schema->$column;
      if (!$first) {
        $sql .= ', ';
      }
      else {
        $first = false;
      }
      $sql .= $column;
      $sql .= ' ' . $this->convertType($type);
    }
    foreach ($schema->getIndexes() as $index => $options) {
      $sql .= ', ';
      if ($index == 'PRIMARY') {
        $sql .= 'PRIMARY KEY (';
      }
      else if ($options['unique']) {
        $sql .= 'UNIQUE (';
      }
      else {
        $sql .= 'INDEX (';
      }
      $sql .= implode(', ', $options['columns']) . ')';
    }
    $sql .= ') CHARACTER SET utf8';
    $this->db->rawQuery($sql);
  }

  public function dropTable($table) {
    $sql = 'DROP TABLE ' . $this->db->tableName($table);
    $this->db->rawQuery($sql);
  }

  public function addColumn($table, $column, DataType $type) {
    $sql = 'ALTER TABLE ' . $this->db->tableName($table) . ' ADD ' . $column;
    $sql .= ' ' . $this->convertType($type);
    $this->db->rawQuery($sql);
  }

  public function deleteColumn($table, $column) {
    $sql = 'ALTER TABLE ' . $this->db->tableName($table) . ' DROP ' . $column;
    $this->db->rawQuery($sql);
  }

  public function alterColumn($table, $column, DataType $type) {
    $sql = 'ALTER TABLE ' . $this->db->tableName($table) . ' CHANGE ' . $column
        . ' ' . $column;
    $sql .= ' ' . $this->convertType($type);
    $this->db->rawQuery($sql);
  }

  public function createIndex($table, $index, $options = array()) {
    $sql = 'ALTER TABLE ' . $this->db->tableName($table);
    if ($index == 'PRIMARY') {
      $sql .= ' ADD PRIMARY KEY';
    }
    else if ($options['unique']) {
      $sql .= ' ADD UNIQUE ' . $index;
    }
    else {
      $sql .= ' ADD INDEX ' . $index;
    }
    $sql .= ' (';
    $sql .= implode(', ', $options['columns']);
    $sql .= ')';
    $this->db->rawQuery($sql);
  }

  public function deleteIndex($table, $index) {
    $sql = 'ALTER TABLE ' . $this->db->tableName($table);
    if ($index == 'PRIMARY') {
      $sql .= ' DROP PRIMARY KEY';
    }
    else {
      $sql .= ' DROP INDEX ' . $index;
    }
    $this->db->rawQuery($sql);
  }

  public function alterIndex($table, $index, $options = array()) {
    $sql = 'ALTER TABLE ' . $this->db->tableName($table);
    if ($index == 'PRIMARY') {
      $sql .= ' DROP PRIMARY KEY';
    }
    else {
      $sql .= ' DROP INDEX ' . $index;
    }
    $sql .= ', ';
    if ($index == 'PRIMARY') {
      $sql .= ' ADD PRIMARY KEY';
    }
    else if ($options['unique']) {
      $sql .= ' ADD UNIQUE ' . $index;
    }
    else {
      $sql .= ' ADD INDEX ' . $index;
    }
    $sql .= ' (';
    $sql .= implode(', ', $options['columns']);
    $sql .= ')';
    $this->db->rawQuery($sql);
  }
}
