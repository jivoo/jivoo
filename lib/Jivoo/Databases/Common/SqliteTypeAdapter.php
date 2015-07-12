<?php
// Jivoo
// Copyright (c) 2015 Niels Sonnich Poulsen (http://nielssp.dk)
// Licensed under the MIT license.
// See the LICENSE file or http://opensource.org/licenses/MIT for more information.
namespace Jivoo\Databases\Common;

use Jivoo\Databases\IMigrationTypeAdapter;
use Jivoo\Databases\Schema;
use Jivoo\Models\DataType;
use Jivoo\Core\Utilities;

/**
 * Type and migration adapter for SQLite database drivers.
 */
class SqliteTypeAdapter implements IMigrationTypeAdapter {
  /**
   * @var SqlDatabase Database.
   */
  private $db;

  /**
   * Construct type adapter.
   * @param SqlDatabase $db Database.
   */
  public function __construct(SqlDatabase $db) {
    $this->db = $db;
  }

  /**
   * {@inheritdoc}
   */
  public function encode(DataType $type, $value) {
    $value = $type->convert($value);
    if (!isset($value))
      return 'NULL';
    switch ($type->type) {
      case DataType::BOOLEAN:
        return $value ? 1 : 0;
      case DataType::INTEGER:
      case DataType::DATETIME:
      case DataType::DATE:
        return intval($value);
      case DataType::FLOAT:
        return floatval($value);
      case DataType::STRING:
      case DataType::TEXT:
      case DataType::BINARY:
      case DataType::ENUM:
        return $this->db->quoteString($value);
      case DataType::OBJECT:
        return $this->db->quoteString(Json::encode($value));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function decode(DataType $type, $value) {
    if (!isset($value))
      return null;
    switch ($type->type) {
      case DataType::BOOLEAN:
        return $value != 0;
      case DataType::INTEGER:
      case DataType::DATE:
      case DataType::DATETIME:
        return intval($value);
      case DataType::FLOAT:
        return floatval($value);
      case DataType::TEXT:
      case DataType::BINARY:
      case DataType::STRING:
      case DataType::ENUM:
        return strval($value);
      case DataType::OBJECT:
        return Json::decode($value);
    }
  }

  /**
   * Convert a schema type to an SQLite type
   * @param DataType $type Type.
   * @param bool $isPrimaryKey True if primary key.
   * @return string SQLite type.
   */
  public function fromDataType(DataType $type, $isPrimaryKey = false) {
    $primaryKey = '';
    if ($isPrimaryKey)
      $primaryKey = ' PRIMARY KEY';
    switch ($type->type) {
      case DataType::INTEGER:
        if ($type->size == DataType::BIG)
          $column = 'INTEGER(8)';
        else if ($type->size == DataType::SMALL)
          $column = 'INTEGER(2)';
        else if ($type->size == DataType::TINY)
          $column = 'INTEGER(1)';
        else
          $column = 'INTEGER';
        if ($isPrimaryKey and $type->autoIncrement)
          $primaryKey .= ' AUTOINCREMENT';
        break;
      case DataType::FLOAT:
        $column = 'REAL';
        break;
      case DataType::STRING:
        $column = 'TEXT(' . $type->length . ')';
        break;
      case DataType::BOOLEAN:
        $column = 'INTEGER(1)';
        break;
      case DataType::BINARY:
        $column = 'BLOB';
        break;
      case DataType::DATE:
        $column = 'INTEGER';
        break;
      case DataType::DATETIME:
        $column = 'INTEGER';
        break;
      case DataType::TEXT:
      case DataType::ENUM:
      case DataType::OBJECT:
      default:
        $column = 'TEXT';
        break;
    }
    $column .= $primaryKey;
    if ($type->notNull)
      $column .= ' NOT';
    $column .= ' NULL';
    if (isset($type->default))
      $column .= $this->db->escapeQuery(' DEFAULT ?', $type->default);
    return $column;
  }
  
  /**
   * Convert output of PRAGMA to DataType.
   * @param array $row Row result.
   * @throws \Exception If type unsupported.
   * @return DataType The type.
   */
  private function toDataType($row) {
    if (preg_match('/ *([^ (]+) *(\(([0-9]+)\))? */i', $row['type'], $matches) !== 1)
      throw new \Exception(tr('Cannot read type "%1" for column: %2', $row['type'], $row['name']));
    $actualType = strtolower($matches[1]);
    $length = isset($matches[3]) ? $matches[3] : 0;
    $null = (isset($row['notnull']) and $row['notnull'] != '1');
    $default = null;
    if (isset($row['dflt_value']))
      $default = stripslashes(preg_replace('/^\'|\'$/', '', $row['dflt_value']));
    switch ($actualType) {
      case 'integer':
        return DataType::integer(DataType::BIG, $null, intval($default));
      case 'real':
        return DataType::float($null, floatval($default));
      case 'text':
        return DataType::text($null, $default);
      case 'blob':
        return DataType::binary($null, $default);
    }
    throw new \Exception(tr(
      'Unsupported SQLite type for column: %1', $row['name']
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getTableSchema($table) {
    $result = $this->db->rawQuery('PRAGMA table_info("' . $this->db->tableName($table) . '")');
    $schema = new Schema($table);
    $primaryKey = array();
    while ($row = $result->fetchAssoc()) {
      $column = $row['name'];
      if (isset($row['pk']) and $row['pk'] == '1')
        $primaryKey[] = $column;
      $schema->addField($column, $this->toDataType($row));
    }
    $schema->setPrimaryKey($primaryKey);
    $result = $this->db->rawQuery('PRAGMA index_list("' . $this->db->tableName($table) . '")');
    while ($row = $result->fetchAssoc()) {
      $index = $row['name'];
      $unique = $row['unique'] == 1;
      $name = preg_replace(
        '/^' . preg_quote($this->db->tableName($table) . '_', '/') . '/',
        '', $index, 1, $count
      );
      if ($count == 0)
        continue;
      $columnResult = $this->db->rawQuery('PRAGMA index_info("' . $index . '")');
      $columns = array();
      while ($row = $columnResult->fetchAssoc()) {
        $columns[] = $row['name'];
      }
      if ($unique)
        $schema->addUnique($name, $columns);
      else
        $schema->addIndex($name, $columns);
    }
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function tableExists($table) {
    $result = $this->db->rawQuery(
      'PRAGMA table_info("' . $this->db->tableName($table) . '")');
    return $result->hasRows();
  }

  /**
   * {@inheritdoc}
   */
  public function getTables() {
    $prefix = $this->db->tableName('');
    $prefixLength = strlen($prefix);
    $result = $this->db->rawQuery('SELECT name FROM sqlite_master WHERE type = "table"');
    $tables = array();
    while ($row = $result->fetchRow()) {
      $name = $row[0];
      if (substr($name, 0, $prefixLength) == $prefix) {
        $name = substr($name, $prefixLength);
        $tables[] = Utilities::underscoresToCamelCase($name);
      }
    }
    return $tables;
  }

  /**
   * {@inheritdoc}
   */
  public function createTable(Schema $schema) {
    $sql = 'CREATE TABLE "' . $this->db->tableName($schema->getName()) . '" (';
    $columns = $schema->getFields();
    $first = true;
    $primaryKey = $schema->getPrimaryKey();
    $singlePrimary = count($primaryKey) ==  1;
    foreach ($columns as $column) {
      $type = $schema->$column;
      if (!$first) {
        $sql .= ', ';
      }
      else {
        $first = false;
      }
      $sql .= $column;
      $sql .= ' ' . $this->fromDataType($type, $singlePrimary and $primaryKey[0] == $column);
    }
    if (!$singlePrimary) {
      $sql .= ', PRIMARY KEY (' . implode(', ', $schema->getPrimaryKey()) . ')';
    }
    $sql .= ')';
    $this->db->rawQuery($sql);
    foreach ($schema->getIndexes() as $index => $options) {
      if ($index == 'PRIMARY') {
        continue;
      }
      $sql = 'CREATE';
      if ($options['unique']) {
        $sql .= ' UNIQUE';
      }
      $sql .= ' INDEX "';
      $sql .= $this->db->tableName($schema->getName()) . '_' . $index;
      $sql .= '" ON "' . $this->db->tableName($schema->getName());
      $sql .= '" (';
      $sql .= implode(', ', $options['columns']) . ')';
      $this->db->rawQuery($sql);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function renameTable($table, $newName) {
    throw new \Exception('Not implemented');
    try {
      $current = $this->db->getSchema()->getSchema($table);
      $this->db->beginTransaction();
      $newSchema = $current->copy($newName);
      $this->createTable($newSchema);
      $sql = 'INSERT INTO ' . $this->db->quoteTableName($newSchema->getName());
      $sql .= ' SELECT * FROM ' . $this->db->quoteTableName($table);
      $this->db->rawQuery($sql);
      $this->dropTable($table);
      $this->db->commit();
    }
    catch (\Exception $e) {
      $this->db->rollback();
      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function dropTable($table) {
    $sql = 'DROP TABLE "' . $this->db->tableName($table) . '"';
    $this->db->rawQuery($sql);
  }

  /**
   * {@inheritdoc}
   */
  public function addColumn($table, $column, DataType $type) {
    $sql = 'ALTER TABLE "' . $this->db->tableName($table) . '" ADD ' . $column;
    $sql .= ' ' . $this->fromDataType($type);
    $this->db->rawQuery($sql);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteColumn($table, $column) {
    try {
      $current = $this->db->getSchema()->getSchema($table);
      $this->db->beginTransaction();
      $temp = $current->copy($table . '_MigrationBackup');
      $this->createTable($temp);
      $sql = 'INSERT INTO ' . $this->db->quoteTableName($temp->getName());
      $sql .= ' SELECT * FROM ' . $this->db->quoteTableName($table);
      $this->db->rawQuery($sql);
      $this->dropTable($table);
      $newSchema = $current->copy($table);
      unset($newSchema->$column);
      $this->createTable($newSchema);
      $sql = 'INSERT INTO ' . $this->db->quoteTableName($table);
      $sql .= ' SELECT ' . implode(', ', $newSchema->getFields()) . ' FROM ' . $this->db->quoteTableName($temp->getName());
      $this->db->rawQuery($sql);
      $this->dropTable($temp->getName());
      $this->db->commit();
    }
    catch (\Exception $e) {
      $this->db->rollback();
      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alterColumn($table, $column, DataType $type) {
    try {
      $current = $this->db->getSchema()->getSchema($table);
      $this->db->beginTransaction();
      $temp = $current->copy($table . '_MigrationBackup');
      $this->createTable($temp);
      $sql = 'INSERT INTO ' . $this->db->quoteTableName($temp->getName());
      $sql .= ' SELECT * FROM ' . $this->db->quoteTableName($table);
      $this->db->rawQuery($sql);
      $this->dropTable($table);
      $newSchema = $current->copy($table);
      $newSchema->$column = $type;
      $this->createTable($newSchema);
      $sql = 'INSERT INTO ' . $this->db->quoteTableName($table);
      $sql .= ' SELECT * FROM ' . $this->db->quoteTableName($temp->getName());
      $this->db->rawQuery($sql);
      $this->dropTable($temp->getName());
      $this->db->commit();
    }
    catch (\Exception $e) {
      $this->db->rollback();
      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function renameColumn($table, $column, $newName) {
    try {
      $current = $this->db->getSchema()->getSchema($table);
      $this->db->beginTransaction();
      $temp = $current->copy($table . '_MigrationBackup');
      $this->createTable($temp);
      $sql = 'INSERT INTO ' . $this->db->quoteTableName($temp->getName());
      $sql .= ' SELECT * FROM ' . $this->db->quoteTableName($table);
      $this->db->rawQuery($sql);
      $this->dropTable($table);
      $newSchema = $current->copy($table);
      $type = $newSchema->$column;
      unset($newSchema->$column);
      $newSchema->$newName = $type;
      $this->createTable($newSchema);
      $columns = array();
      foreach ($temp->getFields() as $field) {
        if ($field != $column)
          $columns[] = $field;
      }
      $columns[] = $column;
      $sql = 'INSERT INTO ' . $this->db->quoteTableName($table);
      $sql .= ' SELECT ' . implode(', ', $columns) . ' FROM ' . $this->db->quoteTableName($temp->getName());
      $this->db->rawQuery($sql);
      $this->dropTable($temp->getName());
      $this->db->commit();
    }
    catch (\Exception $e) {
      $this->db->rollback();
      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createIndex($table, $index, $options = array()) {
    $sql = 'CREATE';
    if ($options['unique']) {
      $sql .= ' UNIQUE';
    }
    $sql .= ' INDEX "';
    $sql .= $this->db->tableName($table) . '_' . $index;
    $sql .= '" ON "' . $this->db->tableName($table);
    $sql .= '" (';
    $sql .= implode(', ', $options['columns']) . ')';
    $this->db->rawQuery($sql);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteIndex($table, $index) {
    $sql = 'DROP INDEX "';
    $sql .= $this->db->tableName($table) . '_' . $index . '"';
    $this->db->rawQuery($sql);
  }

  /**
   * {@inheritdoc}
   */
  public function alterIndex($table, $index, $options = array()) {
    try {
      $this->db->beginTransaction();
      $this->deleteIndex($table, $index);
      $this->createIndex($table, $index, $options);
      $this->db->commit();
    }
    catch (\Exception $e) {
      $this->db->rollback();
      throw $e;
    }
  }
}
