<?php
/**
 * Result set for MySQLi driver.
 * @package Jivoo\Databases\Drivers\Mysqli
 */
class MysqliResultSet implements IResultSet {
  /**
   * @var mysqli_result MySQLi result object.
   */
  private $mysqliResult;
  
  /**
   * @var array[] List of saved rows.
   */
  private $rows = array();

  /**
   * Construct result set.
   * @param mysqli_result $result MySQLi result object.
   */
  public function __construct(mysqli_result $result) {
    $this->mysqliResult = $result;
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
    $row = $this->mysqliResult->fetch_row();
    return $row === null ? false : $row;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchAssoc() {
    if (!empty($this->rows)) {
      return array_shift($this->rows);
    }
    $assoc = $this->mysqliResult->fetch_assoc();
    return $assoc === null ? false : $assoc;
  }
}
