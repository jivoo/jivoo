<?php
class UpdateQuery extends Query {

  private $table;
  private $orderBy;
  private $descending;
  private $limit;
  private $where;
  private $whereVars;
  private $offset;

  private $sets = array();

  public static function create($table = NULL) {
    $query = new self();
    $query->table = $table;
    $query->descending = FALSE;
    $query->offset = 0;
    return $query;
  }

  public function setTable($table) {
    $this->table = $table;
    return $this;
  }

  public function set($column, $value = null) {
    if (is_array($column)) {
      foreach ($column as $col => $val) {
        $this->set($col, $val);
      }
    }
    else {
      $this->sets[$column] = $value;
    }
    return $this;
  }

  public function limit($limit) {
    $this->limit = (int)$limit;
    return $this;
  }

  public function offset($offset) {
    $this->offset = (int)$offset;
    return $this;
  }

  public function where($clause) {
    $this->where = $clause;
    return $this;
  }

  public function addVar($var) {
    $this->whereVars[] = $var;
    return $this;
  }

  public function orderBy($column) {
    $this->orderBy = $column;
    $this->descending = false;
    return $this;
  }

  public function orderByDescending($column) {
    $this->orderBy = $column;
    $this->descending = true;
    return $this;
  }

  public function toSql(IDatabase $db) {
    $sqlString = 'UPDATE ' . $db->tableName($this->table);
    if (!empty($this->sets)) {
      $sqlString .= ' SET';
      reset($this->sets);
      while (($value = current($this->sets)) !== FALSE) {
        $sqlString .= ' ' . $db->escapeQuery(key($this->sets) . '  = ?', array($value));
        if (next($this->sets) !== FALSE) {
          $sqlString .= ',';
        }
      }
    }
    if (isset($this->where)) {
      $sqlString .= ' WHERE ' . $db->escapeQuery($this->where, $this->whereVars);
    }
    if (isset($this->orderBy)) {
      $sqlString .= ' ORDER BY ' . $this->orderBy;
      $sqlString .= $this->descending ? ' DESC' : ' ASC';
    }
    if (isset($this->limit)) {
      $sqlString .= ' LIMIT ' . $this->offset . ', ' . $this->limit;
    }
    return $sqlString;
  }

}