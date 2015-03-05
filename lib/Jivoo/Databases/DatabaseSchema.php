<?php
// Jivoo
// Copyright (c) 2015 Niels Sonnich Poulsen (http://nielssp.dk)
// Licensed under the MIT license.
// See the LICENSE file or http://opensource.org/licenses/MIT for more information.
namespace Jivoo\Databases;

use Jivoo\Models\ISchema;

/**
 * A database schema.
 */
class DatabaseSchema implements IDatabaseSchema {
  /**
   * @var Schema[] Associative array of names and schema.
   */
  private $schemas = array();
  
  /**
   * @var string[] List of table names.
   */
  private $tables = array();
  
  /**
   * Construct database schema.
   * @param Schema[] $schemas Table schemas.
   */
  public function __construct($schemas = array()) {
    foreach ($schemas as $schema)
      $this->addSchema($schema);
  }

  /**
   * {@inheritdoc}
   */
  public function getTables() {
    return $this->tables;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchema($table) {
    if (isset($this->schemas[$table]))
      return $this->schemas[$table];
    return null;
  }
  
  /**
   * Add a schema (and table).
   * @param ISchema $schema Schema.
   */
  public function addSchema(ISchema $schema) {
    $name = $schema->getName();
    if (!in_array($name, $this->tables))
      $this->tables[] = $name;
    $this->schemas[$name] = $schema;
  }
}