<?php

class Request {

  private $path;

  private $query;
  
  private $fragment = NULL;

  private $data;

  public function __construct() {
    $url = $_SERVER['REQUEST_URI'];
    $request = parse_url($url);
    if (isset($request['fragment'])) {
      $this->fragment = $request['fragment'];
    }
    $path = urldecode($request['path']);
    if (WEBPATH != '/') {
      $path = str_replace(WEBPATH, '', $path);
    }
    $path = explode('/', $path);
    $this->path = array();
    foreach ($path as $dir) {
      if (!empty($dir)) {
        $this->path[] = $dir;
      }
    }
    
    $this->query = $_GET;
    $this->data = $_POST;
  }

  public function __get($name) {
    switch ($name) {
      case 'path':
      case 'data':
      case 'query':
      case 'fragment':
        return $this->$name;
    }
  }
  
  public function __set($name, $value) {
    switch ($name) {
      case 'path':
      case 'query':
      case 'fragment':
        $this->$name = $value;
    }
  }
  
  public function unsetQuery($key = NULL) {
    if (isset($key)) {
      $this->query = array();
    }
    else {
      unset($this->query[$key]);
    }
  }

  public function isGet() {
    return $_SERVER['REQUEST_METHOD'] == 'GET';
  }

  public function isPost() {
    return $_SERVER['REQUEST_METHOD'] == 'POST';
  }

  public function isAjax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
  }

}
