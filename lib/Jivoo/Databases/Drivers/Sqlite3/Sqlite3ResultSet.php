<?php
/**
 * Result set for SQLite3 database driver.
 * @package Jivoo\Databases\Drivers\Sqlite3
 */
class Sqlite3ResultSet implements IResultSet {
  /**
   * @var SQLite3Result SQLite3 result object.
   */
  private $result;
  
  /**
   * @var array[] List of saved rows.
   */
  private $rows = array();

  /**
   * Construct result set.
   * @param SQLite3Result $result SQLITE3 result object.
   */
  public function __construct(SQLite3Result $result) {
    $this->result = $result;
    while ($row = $result->fetchArray(SQLITE3_BOTH)) {
      $this->allRows[] = $row;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasRows() {
    return ($this->rows[] = $this->fetchAssoc()) !== false;
  }

  /**
   * Get ordered array from associative array.
   * @param array $assoc Associative array.
   * @return mixed[] Ordered array.
   */
  private function rowFromAssoc($assoc) {
    return array_values($assoc);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchRow() {
    if (!empty($this->rows)) {
      return $this->rowFromAssoc(array_shift($this->rows));
    }
    return $this->result
      ->fetchArray(SQLITE3_NUM);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAssoc() {
    if (!empty($this->rows)) {
      return array_shift($this->rows);
    }
    return $this->result
      ->fetchArray(SQLITE3_ASSOC);
  }
}
