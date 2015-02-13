<?php
/**
 * A database driver that can be loaded by the {@see Databases} module.
 */
abstract class LoadableDatabase extends Module implements IMigratableDatabase {
  /**
   * @var IDatabaseSchema Schema.
   */
  private $schema;
  
  /**
   * @var string[] List of table names.
   */
  private $tableNames;
  
  /**
   * @var IMigrationTypeAdapter Migration adapter.
   */
  private $migrationAdapter;
  
  /**
   * @var Table[] Tables.
   */
  private $tables;
  
  /**
   * Construct database.
   * @param App $app Associated application.
   * @param IDatabaseSchema $schema Database schema.
   * @param array $options Associative array of options for driver.
   */
  public final function __construct(App $app, IDatabaseSchema $schema, $options = array()) {
    parent::__construct($app);
    $this->schema = $schema;
    $this->init($options);
    $this->migrationAdapter = $this->getMigrationAdapter();
    $this->tableNames = $this->getTables();
    foreach ($this->tableNames as $table) {
      $this->tables[$table] = $this->getTable($table);
    }
  }

  /**
   * Get table.
   * @param string $table Table name.
   * @return Table Table.
   */
  public function __get($table) {
    if (!isset($this->tables[$table])) {
      throw new TableNotFoundException(
        tr('Table not found: "%1"', $table)
      );
    }
    return $this->tables[$table];
  }
  
  /**
   * Whether or not table exists.
   * @param string $table Table name.
   * @return bool True if table exists.
   */
  public function __isset($table) {
    return isset($this->tables[$table]);
  }
  
  /**
   * Database driver initialization.
   * @param array $options Associative array of options for driver.
   */
  protected abstract function init($options);
  
  /**
   * Get migration and type adapter.
   * @return IMigrationTypeAdapter Migration and type adapter.
   */
  protected abstract function getMigrationAdapter();
  
  /**
   * {@inheritdoc}
   */
  public function getSchema() {
    return $this->schema;
  }

  /**
   * {@inheritdoc}
   */
  public function setSchema(IDatabaseSchema $schema) {
    $this->schema = $schema;
    foreach ($schema->getTables() as $table) {
      $tableSchema = $schema->getSchema($table);
      if (!in_array($table, $this->tableNames)) {
        $this->tableNames[] = $table;
        $this->tables[$table] = $this->getTable($table);
      }
      $this->$table->setSchema($tableSchema);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function refreshSchema() {
    $this->schema = new DatabaseSchema();
    foreach ($this->tableNames as $table) {
      $schema = $this->getTableSchema($table);
      $this->schema->addSchema($schema);
      $this->$table->setSchema($schema);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTables() {
    return $this->migrationAdapter->getTables();
  }

  /**
   * {@inheritdoc}
   */
  public function getTableSchema($table) {
    return $this->migrationAdapter->getTableSchema($table);
  }

  /**
   * {@inheritdoc}
   */
  public function createTable(Schema $schema) {
    $this->migrationAdapter->createTable($schema);
  }

  /**
   * {@inheritdoc}
   */
  public function renameTable($table, $newName) {
    $this->migrationAdapter->renametable($table, $newName);
  }

  /**
   * {@inheritdoc}
   */
  public function dropTable($table) {
    $this->migrationAdapter->dropTable($table);
  }

  /**
   * {@inheritdoc}
   */
  public function addColumn($table, $column, DataType $type) {
    $this->migrationAdapter->addColumn($table, $column, $type);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteColumn($table, $column) {
    $this->migrationAdapter->deleteColumn($table, $column);
  }

  /**
   * {@inheritdoc}
   */
  public function alterColumn($table, $column, DataType $type) {
    $this->migrationAdapter->alterColumn($table, $column, $type);
  }

  /**
   * {@inheritdoc}
   */
  public function renameColumn($table, $column, $newName) {
    $this->migrationAdapter->renameColumn($table, $column, $newName);
  }

  /**
   * {@inheritdoc}
   */
  public function createIndex($table, $index, $options = array()) {
    $this->migrationAdapter->createIndex($table, $index, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteIndex($table, $index) {
    $this->migrationAdapter->deleteIndex($table, $index);
  }

  /**
   * {@inheritdoc}
   */
  public function alterIndex($table, $index, $options = array()) {
    $this->migrationAdapter->alterIndex($table, $index, $options);
  }
} 