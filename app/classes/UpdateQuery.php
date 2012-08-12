<?php
class UpdateQuery extends Query {
  protected $orderBy;
  protected $limit;
  protected $where;
  protected $offset = 0;
  protected $sets = array();

  public function __construct() {
    $this->where = new Condition();
  }

  public function __call($method, $args) {
    switch ($method) {
      case 'and':
        call_user_func_array(array($this->where, 'andWhere'), $args);
        return $this;
      case 'or':
        call_user_func_array(array($this->where, 'orWhere'), $args);
        return $this;
    }
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
    $args = func_get_args();
    call_user_func_array(array($this->where, 'where'), $args);
    return $this;
  }

  public function andWhere($clause) {
    $args = func_get_args();
    call_user_func_array(array($this->where, 'andWhere'), $args);
    return $this;
  }

  public function orWhere($clause) {
    $args = func_get_args();
    call_user_func_array(array($this->where, 'orWhere'), $args);
    return $this;
  }

  public function addVar($var) {
    $this->where->addVar($var);
    return $this;
  }

  public function orderBy($column) {
    $this->orderBy[] = array(
      'column' => $column,
      'descending' => FALSE
    );
    return $this;
  }

  public function orderByDescending($column) {
    $this->orderBy[] = array(
      'column' => $column,
      'descending' => TRUE
    );
    return $this;
  }

  public function reverseOrder() {
    foreach ($this->orderBy as $key => $orderBy) {
      $this->orderBy[$key]['descending'] = !$orderBy['descending'];
    }
    return $this;
  }
}
