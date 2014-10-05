<?php
/**
 * Represents a database table schema
 * @package Jivoo\Database
 */
class Schema implements ISchema {
  /**
   * @var string[] List of column names
   */
  private $_fields = array();
  
  /**
   * @var bool Whether or not schema is read only
   */
  private $_readOnly = false;
  
  /**
   * @var string Name of table
   */
  private $_name = 'undefined';

  /**
   * @var array List of indexes
   */
  private $_indexes = array();
  
  private $_revision = 0;

  /**
   * Constructor
   * @param string $name Name of schema
  */
  public function __construct($name = null) {
    $className = get_class($this);
    if ($className != __CLASS__) {
      if (!isset($name)) {
        $name = preg_replace('/Schema$/', '', $className);
      }
      if (defined($className . '::REVISION'))
        $this->_revision = constant($className . '::REVISION');
      $this->createSchema();
      $this->_readOnly = true;
    }
    if (isset($name)) {
      $this->_name = $name;
    }
  }

  /**
   * Get information about column
   * @param string $column Column name
   * @return DataType Type of field
   */
  public function __get($field) {
    if (isset($this->_fields[$field])) {
      return $this->_fields[$field];
    }
    throw new InvalidPropertyException(tr('Invalid property: %1', $field));
  }

  /**
   * Whether or not a column exists in schema
   * @param string $column Column name
   * @return bool True if it does, false otherwise
   */
  public function __isset($field) {
    return isset($this->_fields[$field]);
  }

  public function __set($field, DataType $type) {
    if (!$this->_readOnly) {
      $this->_fields[$field] = $type;
    }
  }
  
  public function getRevision() {
    return $this->_revision;
  }

  public function addAutoIncrementId($id = 'id') {
    if (!$this->_readOnly) {
      $this->$id = DataType::integer(DataType::AUTO_INCREMENT | DataType::UNSIGNED);
      $this->setPrimaryKey($id);
    }
  }

  public function addTimestamps($created = 'created', $updated = 'updated') {
    if (!$this->_readOnly) {
      $this->$created = DataType::dateTime();
      $this->$updated = DataType::dateTime();
    }
  }

  public function createValidationRules(Validator $validator) {
    foreach ($this->_fields as $field => $type) {
      $type->createValidationRules($validator->$field);
    }
    foreach ($this->_indexes as $index) {
      if ($index['unique'] and count($index['columns']) == 1) {
        $field = $index['columns'][0];
        $validator->$field->unique = true;
      }
    }
  }

  public function getFields() {
    return array_keys($this->_fields);
  }

  /**
   * Get name of schema
   * @return string Name
   */
  public function getName() {
    return $this->_name;
  }

  /**
   * Create schema
   */
  protected function createSchema() { }

  /**
   * Add a field to schema
   * @param string $column Column name
   * @param array $info Column information
   */
  public function addField($name, DataType $type) {
    if (!$this->_readOnly) {
      $this->_fields[$name] = $type;
    }
  }

  /**
   * Set primary key
   * @param string|string[] $columns An array of column names or a single column
   * name
   * @param string $columns,... Additional column names (if $columns is a single
   * column name)
   */
  public function setPrimaryKey($columns) {
    if (!is_array($columns)) {
      $params = func_get_args();
      if (count($params) > 1) {
        $columns = $params;
      }
      else {
        $columns = array($columns);
      }
    }
    $this->_indexes['PRIMARY'] = array(
      'columns' => $columns,
      'unique' => true
    );
  }
  
  /**
   * Get columns of primary key
   * @return string[] List of column names or empty array if no primary key
   */
  public function getPrimaryKey() {
    if (!isset($this->_indexes['PRIMARY'])) {
      return array();
    }
    return $this->_indexes['PRIMARY']['columns'];
  }
  
  /**
   * Check if the column is part of the primary key
   * @param string $column Column name
   * @return boolean True if part of primary key, false otherwise
   */
  public function isPrimaryKey($column) {
    if (!isset($this->_indexes['PRIMARY'])) {
      return false;
    }
    return in_array($column, $this->_indexes['PRIMARY']['columns']);
  }

  /**
   * Add a unique index to schema
   * @param string $index Index name
   * @param string|string[] $columns An array of column names or a single column
   * name
   * @param string $columns,... Additional column names (if $columns is a single
   * column name)
   */
  public function addUnique($name, $columns) {
    if (!is_array($columns)) {
      $params = func_get_args();
      if (count($params) > 2) {
        array_shift($params);
        $columns = $params;
      }
      else {
        $columns = array($columns);
      }
    }
    if (isset($this->_indexes[$name])) {
      $this->_indexes[$name]['columns'] = array_merge($this->_indexes[$name]['columns'], $columns);
    }
    else {
      $this->_indexes[$name] = array(
        'columns' => $columns,
        'unique' => true
      );
    }
  }

  /**
   * Add an index to schema
   * @param string $index Index name
   * @param string|string[] $columns An array of column names or a single column
   * name
   * @param string $columns,... Additional column names (if $columns is a single
   * column name)
   */
  public function addIndex($name, $columns) {
    if (!is_array($columns)) {
      $params = func_get_args();
      if (count($params) > 2) {
        array_shift($params);
        $columns = $params;
      }
      else {
        $columns = array($columns);
      }
    }
    if (isset($this->_indexes[$name])) {
      $columns = array_merge($this->_indexes[$name]['columns'], $columns);
    }
    if (isset($this->_indexes[$name])) {
      $this->_indexes[$name]['columns'] = array_merge($this->_indexes[$name]['columns'], $columns);
    }
    else {
      $this->_indexes[$name] = array(
        'columns' => $columns,
        'unique' => false
      );
    }
  }
  
  /**
   * Get indexes. The 'PRIMARY'-index is the primary key
   * 
   * The returned array is of the following format:
   * <code>
   * array(
   *   'indexname' => array(
   *     'columns' => array('columnname1', 'columnname2'),
   *     'unique' => true
   *   )
   * )
   * </code>
   * @return array Associative array of index names and info
   */
  public function getIndexes() {
    return $this->_indexes;
  }
  
  /**
   * Check whether or not an index exists
   * @param string $name Index name
   */
  public function indexExists($name) {
    return isset($this->_indexes[$name]);
  }
  
  /**
   * Get information about an index.
   * @param string $name Index name
   * @return array Associative array with two keys: 'columns' is a list of
   * column names and 'unique' is a boolean.
   */
  public function getIndex($name) {
    return $this->_indexes[$name];
  }
  
  public function migrate(MigratableDatabase $db, $fromRevision = 0) {
    $i = $fromRevision + 1;
    try {
      for (; $i <= $this->_revision; $i++) {
        $method = 'up' . $i;
        if (is_callable(array($this, $method))) {
          $this->$method($db);
        }
      }
    }
    catch (Exception $e) {
      for (; $i > $fromRevision; $i--) {
        $method = 'down' . $i;
        if (is_callable(array($this, $method))) {
          $this->$method($db);
        }
      }
      throw $e;
    }
  }
  
  /**
   * Export schema to PHP class
   * @param string $package Package (for documentation)
   * @return string PHP source
   */
  public function export($package = 'Core') {
    $source = '<?php' . PHP_EOL;
    $source .= '/**' . PHP_EOL;
    $source .= ' * Automatically generated schema for ' . $this->_name
    . ' table' . PHP_EOL;
    $source .= ' * @package ' . $package . PHP_EOL;
    $source .= ' */' . PHP_EOL;
    $source .= 'class ' . $this->_name . 'Schema extends Schema {' . PHP_EOL;
    $source .= '  protected function createSchema() {' . PHP_EOL;
    foreach ($this->_schema as $column => $info) {
      $source .= '    $this->add' . ucfirst($info['type']) . '(';
      $source .= var_export($column, true);
      if ($info['type'] == 'string') {
        $source .= ', ' . var_export($info['length'], true);
      }
      $flags = array();
      if (isset($info['autoIncrement']) AND $info['autoIncrement']) {
        $flags[] = 'Schema::AUTO_INCREMENT';
      }
      if (isset($info['null']) AND !$info['null']) {
        $flags[] = 'Schema::NOT_NULL';
      }
      if (isset($info['unsigned']) AND $info['unsigned']) {
        $flags[] = 'Schema::UNSIGNED';
      }
      if (!empty($flags) OR isset($info['default'])) {
        if (empty($flags)) {
          $source .= ', 0';
        }
        else {
          $source .= ', ' . implode(' | ', $flags);
        } 
        if (isset($info['default'])) {
          $source .= ', ' . var_export($info['default'], true);
        }
      }
      $source .= ');' . PHP_EOL;
    }
  
    if (isset($this->_indexes['PRIMARY'])) {
      $primaryKeyColumns = array();
      foreach ($this->_indexes['PRIMARY']['columns'] as $column) {
        $primaryKeyColumns[] = var_export($column, true);
      }
      $source .= '    $this->setPrimaryKey(' . implode(', ', $primaryKeyColumns);
      $source .= ');' . PHP_EOL;
    }
    foreach ($this->_indexes as $index => $info) {
      if ($index == 'PRIMARY') {
        continue;
      }
      if ($info['unique']) {
        $source .= '    $this->addUnique(' . var_export($index, true);
      }
      else {
        $source .= '    $this->addIndex(' . var_export($index, true);
      }
      foreach ($info['columns'] as $column) {
        $source .= ', ' . var_export($column, true);
      }
      $source .= ');' . PHP_EOL;
    }
    $source .= '  }' . PHP_EOL;
    $source .= '}' . PHP_EOL;
    return $source;
  }
}
