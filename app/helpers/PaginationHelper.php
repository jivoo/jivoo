<?php

class PaginationHelper extends ApplicationHelper {
  
  private $limit = 5;
  
  private $count = 0;
  
  private $pages = 1;
  
  private $page = 1;

  private $offset = 0;
  
  public function setCount($count) {
    $this->count = $count;
    return $this;
  }
  
  public function setLimit($limit) {
    precondition($limit > 0);
    $this->limit = $limit;
    return $this;
  }
  
  public function paginate(SelectQuery $select) {    
    $this->pages = max(ceil($this->count / $this->limit), 1);
    $select->limit($this->limit);
    if (isset($this->request->query['page'])) {
      $this->page = (int) $this->request->query['page'];
      $this->page = min($this->page, $this->pages);
      $this->page = max($this->page, 1);
    }
    $this->offset = ($this->page - 1) * $this->limit;
    $select->offset($this->offset);
  }

  public function getCount() {
    return $this->count;
  }

  public function getFrom() {
    return min($this->offset + 1, $this->count);
  }

  public function getTo() {
    return min($this->offset + $this->limit, $this->count);
  }
  
  public function getPage() {
    return $this->page;
  }
  
  public function getPages() {
    return $this->pages;
  }
  
  public function isFirst() {
    return $this->page == 1;
  }

  public function isLast() {
    return $this->page == $this->pages;
  }
  
  public function prevLink($fragment = NULL) {
    return $this->getLink(array(
      'query' => array('page' => $this->page - 1),
      'fragment' => $fragment,
      'mergeQuery' => TRUE
    ));
  }
  
  public function nextLink($fragment = NULL) {
    return $this->getLink(array(
      'query' => array('page' => $this->page + 1),
      'fragment' => $fragment,
      'mergeQuery' => TRUE
    ));
  }

  public function firstLink($fragment = NULL) {
    return $this->getLink(array(
      'query' => array('page' => 1),
      'fragment' => $fragment,
      'mergeQuery' => TRUE
    ));
  }
  
  public function lastLink($fragment = NULL) {
    return $this->getLink(array(
      'query' => array('page' => $this->pages),
      'fragment' => $fragment,
      'mergeQuery' => TRUE
    ));
  }
}
