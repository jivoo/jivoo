<?php
// Jivoo
// Copyright (c) 2015 Niels Sonnich Poulsen (http://nielssp.dk)
// Licensed under the MIT license.
// See the LICENSE file or http://opensource.org/licenses/MIT for more information.
namespace Jivoo\Databases\Common;

use Jivoo\Databases\LoadableDatabase;
use Jivoo\Databases\IMigrationTypeAdapter;
use Jivoo\Core\Utilities;
use Jivoo\Models\DataType;
use Jivoo\Models\IBasicModel;

/**
 * A generic SQL database.
 */
abstract class SqlDatabase extends LoadableDatabase implements ISqlDatabase {
  /**
   * @var string Table prefix.
   */
  protected $tablePrefix = '';

  /**
   * @var IMigrationTypeAdapter Type/migration adapter.
   */
  private $typeAdapter = null;
  
  /**
   * @var array Associative array of table names and {@see SqlTable} objects.
   */
  protected $tables = array();
  
  /**
   * Destruct and close database.
   */
  function __destruct() {
    $this->close();
  }

  /**
   * Create new table object.
   * @param string $table Table name.
   * @return SqlTable Table object.
   */
  protected function getTable($table) {
    return new SqlTable($this->app, $this, $table);
  }

  /**
   * {@inheritdoc}
   */
  protected function getMigrationAdapter() {
    return $this->typeAdapter;
  }
  
  /**
   * Set migration/type adapter.
   * @param IMigrationTypeAdapter $typeAdapter Adapter.
   */
  protected function setTypeAdapter(IMigrationTypeAdapter $typeAdapter) {
    $this->typeAdapter = $typeAdapter;
  }

  /**
   * Convert table name. E.g. "UserSession" to "prefix_user_session".
   * @param string $name Table name.
   * @return string Real table name.
   */
  public function tableName($name) {
    return $this->tablePrefix . Utilities::camelCaseToUnderscores($name);
  }

  /**
   * Quote table name for queries.
   * @param string $name Table name.
   * @return string Quoted table name.
   */
  public function quoteTableName($name) {
    return '`' . $this->tableName($name) . '`';
  }

  /**
   * Escape a string and surround with quotation marks.
   * @param string $string String.
   * @return string String surrounded with quotation marks.
   */
  public abstract function quoteString($string);
  
  /**
   * @var mixed[] Placeholder values.
   */
  private $vars;
  
  /**
   * @var int Current placeholder index.
   */
  private $varCount;
  
  /**
   * Encode a value.
   * @param DataType $type Type.
   * @param mixed $value Value.
   * @return string Encoded string.
   */
  private function encodeValue(DataType $type = null, $value) {
    if (!isset($type))
      return $this->typeAdapter->encode(DataType::detectType($value), $value);
    return $this->typeAdapter->encode($type, $value);
  }

  /**
   * Replace a placeholder with a value.
   * @param array $matches Regex matches.
   * @return string Replacements.
   */
  private function replaceVar($matches) {
    $value = $this->vars[$this->varCount];
    $this->varCount++;
    $type = null;
    if (isset($matches[3]) and $matches[3] == '_') {
      if (!is_string($value)) {
        assume($value instanceof DataType);
        $value = $value->placeholder;
      }
      $matches[3] = ltrim($value, '%');
      $value = $this->vars[$this->varCount];
      $this->varCount++;
    }
    if (isset($matches[3]) and ($matches[3] == 'm' or $matches[3] == 'model')) {
      if (!is_string($value)) {
        assume($value instanceof IBasicModel);
        $value = $value->getName();
      }
      return $this->quoteTableName($value);
    }
    if (isset($matches[3]) and ($matches[3] == 'c' or $matches[3] == 'column')) {
      assume(is_string($value));
      // TODO: escape/validate that the column name is valid'ish
      return $value;
    }
    if (isset($matches[3]) and $matches[3] != '()')
      $type = DataType::fromPlaceholder($matches[3]);
    if (isset($matches[4]) or (isset($matches[3]) and $matches[3] == '()')) {
      assume(is_array($value));
      foreach ($value as $key => $v)
        $value[$key] = $this->encodeValue($type, $v);
      return '(' . implode(', ', $value) . ')';
    }
    return $this->encodeValue($type, $value);
  }
  
  /**
   * Replace table match by quoting and converting the table name.
   * @param array $matches Regex matches.
   * @return string Replacement.
   */
  private function replaceTable($matches) {
    return $this->quoteTableName($matches[1]);
  }
  
  /**
   * Escape a query.
   * 
   * Placeholders (see also {@see DataType::fromPlaceHolder()}:
   * <code>
   * ? // Any scalar value.
   * true // Boolean true
   * false // Boolean false
   * {AnyTableName} // A table name
   * %m %model // A table/model object or name
   * %c %column // A column/field name
   * %_ // A placeholder placeholder, can also be a type, e.g. where(..., 'id = %_', $type, $value)
   * %i %int %integer // An integer value
   * %f %float // A floating point value
   * %s %str %string // A string
   * %t $text // Text
   * %b %bool %boolean // A boolean value
   * %date // A date value
   * %d %datetime // A date/time value
   * %n %bin %binary // A binary object
   * %AnyEnumClassName // An enum value of that class
   * %anyPlaceholder() // An array of values
   * </code>
   * 
   * @todo Could be moved to a more general place.
   * @param string $format Query format, use placeholders instead of values.
   * @param mixed[] $vars List of values to replace Placeholders with.
   * @return string The escaped query.
   */
  public function escapeQuery($format, $vars = array()) {
    $sqlString = '';
    $key = 0;
    $chars = str_split($format);
    if (!is_array($vars)) {
      $vars = func_get_args();
      array_shift($vars);
    }
    $this->vars = $vars;
    $this->varCount = 0;
    $boolean = DataType::boolean();
    $true = $this->encodeValue($boolean, true);
    $false = $this->encodeValue($boolean, false);
    $format = preg_replace('/\btrue\b/i', $true, $format);
    $format = preg_replace('/\bfalse\b/i', $false, $format);
    $format = preg_replace_callback('/\{(.+?)\}/', array($this, 'replaceTable'), $format);
    return preg_replace_callback('/((\?)|%([a-z_\\\\]+))(\(\))?/i', array($this, 'replaceVar'), $format);
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeAdapter() {
    return $this->typeAdapter;
  }

  /**
   * Whether or not a table exists.
   * @param string $table Table name.
   * @return bool True if table exists, false otherwise.
   */
  public function tableExists($table) {
    return $this->typeAdapter->tableExists($table);
  }
  
  /**
   * Get SQL for LIMIT / OFFSET. Default style ("LIMIT 0,1") is used by SQLite
   * and MySQL, override for other implementations.
   * @param int $limit Limit.
   * @param int|null $offset Optional offset.
   * @return string SQL.
   */
  public function sqlLimitOffset($limit, $offset = null) {
    if (isset($offset))
      return 'LIMIT ' . $offset . ', ' . $limit;
    return 'LIMIT ' . $limit;
  }

  /**
   * {@inheritdoc}
   */
  public function beginTransaction() {
    $this->rawQuery('BEGIN');
  }

  /**
   * {@inheritdoc}
   */
  public function commit() {
    $this->rawQuery('COMMIT');
  }

  /**
   * {@inheritdoc}
   */  
  public function rollback() {
    $this->rawQuery('ROLLBACK');
  }
}

