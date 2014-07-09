<?php
/**
 * A database consisting of multiple data sources (tables)
 * @package Jivoo\Database
 */
interface IDatabase {
  /**
   * Get a loaded table from database
   * @param string $table Table name
   * @return IModel|null Table or null if undefined
   */
  public function __get($table);
  
  /**
   * Check if table is loaded
   * @param string $table Table name
   * @return bool True if loaded, false otherwise
   */
  public function __isset($table);
  
  /**
   * Close database connection
   */
  public function close();
  
  /**
   * Get a table object, or create it (with the given schema) if it doesn't exist 
   * @param string $name Table name
   * @return IModel Table
   */
  public function getTable($name, ISchema $schema);
  
  /**
   * Check a table exists in database
   * @param string $name Table name
   * @return bool True if it exists, false otherwise
   */
  public function tableExists($name);
  
  /**
   * Run migration on database
   * @param Schema $schema Table schema
   * @return string Migration status: 'unchanged', 'updated' or 'new'
   */
  public function migrate(Schema $schema);
  
  public function beginTransaction();
  
  public function commit();
  
  public function rollback();
}

/**
 * A database connection has failed
 * @package Jivoo\Database
 */
class DatabaseConnectionFailedException extends Exception {}

/**
 * A database selection has failed
 * @package Jivoo\Database
 */
class DatabaseSelectFailedException extends Exception {}

/**
 * A database query has failed
 * @package Jivoo\Database
 */
class DatabaseQueryFailedException extends Exception {}


class TableNotFoundException extends Exception { }
