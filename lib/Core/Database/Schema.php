<?php
/**
 * Represents a database table schema
 * @package Core\Database
 */
class Schema {
  const UNSIGNED = 0x1;
  const AUTO_INCREMENT = 0x2;
  const NOT_NULL = 0x4;

  private $schema = array();
  private $columns = array();
  private $primaryKey = null;
  private $readOnly = false;
  private $name = 'undefined';

  /**
   * @var array List of indexes in format `array(
   *   'indexname' => array(
   *     'columns' => array('columnname1', 'columnname2'),
   *     'unique' => true
   *   )
   * )`
   */
  private $indexes = array();

  /**
   * Create schema
   * @param string $name Name of schema
  */
  public function __construct($name = null) {
    $className = get_class($this);
    if ($className != __CLASS__) {
      if (!isset($name)) {
        $name = substr($className, 0, -6);
      }
      $this->createSchema();
      $this->readOnly = true;
    }
    if (isset($name)) {
      $this->name = $name;
    }
  }

  /**
   * Get information about column
   * @param string $column Column name
   * @return array Key/value pairs with possible keys:
   * 'type', 'length', 'null', 'default', 'autoIncrement', 'key', 'unsigned'
   */
  public function __get($column) {
    if (isset($this->schema[$column])) {
      return $this->schema[$column];
    }
  }

  /**
   * Whether or not a column exists in schema
   * @param string $column Column name
   * @return bool True if it does, false otherwise
   */
  public function __isset($column) {
    return isset($this->schema[$column]);
  }

  /**
   * Get name of schema
   * @return string Name
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Create schema
   */
  protected function createSchema() { }

  /**
   * Add a column to schema
   * @param string $column Column name
   * @param array $info Column information
   */
  public function addColumn($column, $info = array()) {
    if (!$this->readOnly) {
      $this->columns[] = $column;
      $this->schema[$column] = $info;
    }
  }

  public function addString($name, $length = 255, $flags = 0, $default = null) {
    $this->addColumn($name, array(
      'type' => 'string',
      'length' => $length,
      'null' => ($flags & self::NOT_NULL) == 0,
      'default' => $default
    ));
  }

  public function addInteger($name, $flags = 0, $default = null) {
    $this->addColumn($name, array(
      'type' => 'integer',
      'autoIncrement' => ($flags & self::AUTO_INCREMENT) != 0,
      'unsigned' => ($flags & self::UNSIGNED) != 0,
      'null' => ($flags & self::NOT_NULL) == 0,
      'default' => $default
    ));
  }

  public function addFloat($name, $flags = 0, $default = null) {
    $this->addColumn($name, array(
      'type' => 'float',
      'null' => ($flags & self::NOT_NULL) == 0,
      'default' => $default
    ));
  }

  public function addBoolean($name, $flags = 0, $default = null) {
    $this->addColumn($name, array(
      'type' => 'boolean',
      'null' => ($flags & self::NOT_NULL) == 0,
      'default' => $default
    ));
  }

  public function addText($name, $flags = 0, $default = null) {
    $this->addColumn($name, array(
      'type' => 'text',
      'null' => ($flags & self::NOT_NULL) == 0,
      'default' => $default
    ));
  }

  public function addBinary($name, $flags = 0, $default = null) {
    $this->addColumn($name, array(
      'type' => 'binary',
      'null' => ($flags & self::NOT_NULL) == 0,
      'default' => $default
    ));
  }

  public function addDate($name, $flags = 0, $default = null) {
    $this->addColumn($name, array(
      'type' => 'date',
      'null' => ($flags & self::NOT_NULL) == 0,
      'default' => $default
    ));
  }

  public function addDateTime($name, $flags = 0, $default = null) {
    $this->addColumn($name, array(
      'type' => 'dateTime',
      'null' => ($flags & self::NOT_NULL) == 0,
      'default' => $default
    ));
  }

  public function setPrimaryKey($columns) {
    if (!is_array($columns)) {
      $params = func_get_args();
      if (count($params) <= 1) {
        $columns = $params;
      }
      else {
        $columns = array($columns);
      }
    }
    $this->primaryKey = $columns;
  }
  
  public function getPrimaryKey() {
    return $this->primaryKey;
  }
  
  public function isPrimaryKey($column) {
    return in_array($column, $this->primaryKey);
  }

  /**
   * Add a unique index to schema
   * @param string $index Index name
   * @param string|string[] $columns An array of column names
   * @param string $columns,... Additional column names
   */
  public function addUnique($name, $columns) {
    if (!is_array($columns)) {
      $params = func_get_args();
      if (count($params) <= 2) {
        array_shift($params);
        $columns = $params;
      }
      else {
        $columns = array($columns);
      }
    }
    $this->indexes[$name] = array(
      'columns' => $columns,
      'unique' => true
    );
  }

  /**
   * Add an index to schema
   * @param string $index Index name
   * @param string|string[] $columns An array of column names
   * @param string $columns,... Additional column names
   */
  public function addIndex($name, $columns) {
    if (!is_array($columns)) {
      $params = func_get_args();
      if (count($params) <= 2) {
        array_shift($params);
        $columns = $params;
      }
      else {
        $columns = array($columns);
      }
    }
    $this->indexes[$name] = array(
      'columns' => $columns,
      'unique' => false
    );
  }

  /**
   * Get column names
   * @return string[] Column names
   */
  public function getColumns() {
    return $this->columns;
  }
  
  public function getIndexes() {
    return $this->indexes;
  }
  
  public function indexExists($name) {
    return isset($this->indexes[$name]);
  }
  
  public function getIndex($name) {
    return $this->indexes[$name];
  }
  
  /**
   * Export schema to PHP class
   * @param string $package Package (for documentation)
   * @return string PHP source
   */
  public function export($package = 'Core') {
    $source = '<?php' . PHP_EOL;
    $source .= '/**' . PHP_EOL;
    $source .= ' * Automatically generated schema for ' . $this->name
    . ' table' . PHP_EOL;
    $source .= ' * @package ' . $package . PHP_EOL;
    $source .= ' */' . PHP_EOL;
    $source .= 'class ' . $this->name . 'Schema extends Schema {' . PHP_EOL;
    $source .= '  protected function createSchema() {' . PHP_EOL;
    foreach ($this->schema as $column => $info) {
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
  
    $primaryKeyColumns = array();
    foreach ($this->primaryKey as $column) {
      $primaryKeyColumns[] = var_export($column, true);
    }
    $source .= '    $this->setPrimaryKey(' . implode(', ', $primaryKeyColumns);
    $source .= ');' . PHP_EOL;
    foreach ($this->indexes as $index => $info) {
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