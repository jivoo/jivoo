<?php
/**
 * A class representing a HTTP request
 * @package Core\Routing
 * @property string[] $path The path relative to the application root as an array
 * @property array $query The GET query as an associative array
 * @property string $fragment The fragment
 * @property-read string[] $realPath The original $path
 * @property-read array $data POST data as an associative array
 * @property-read Cookies $cookies Cookie access object
 * @property-read Session $session Session storage access object
 * @property-read string|null $ip The remote address or null if not set
 * @property-read string|null $url The request uri or null if not set
 * @property-read string|null $referer HTTP referer or null if not set
 * @property-read string|null $userAgent HTTP user agent or null if not set
 */
class Request {

  /**
   * @var string[] Original path
   */
  private $realPath;

  /**
   * @var string[] Path as array
   */
  private $path;

  /**
   * @var array GET query
   */
  private $query;

  /**
   * @var Cookies Cookies object
   */
  private $cookies;

  /**
   * @var Session Session object
   */
  private $session;

  /**
   * @var string Fragment 
   */
  private $fragment = null;

  /**
   * @var array POST data
   */
  private $data;
  
  /**
   * @var bool Whether or not request is from mobile browser
   */
  private $mobile = null;

  /**
   * Constructor
   * @param string $sessionPrefix Session prefix to use for session variables
   * @param string $basePath Base path of application
   */
  public function __construct($sessionPrefix = '', $basePath = '/') {
    $url = $_SERVER['REQUEST_URI'];
       
    $request = parse_url($url);
    if (isset($request['fragment'])) {
      $this->fragment = $request['fragment'];
    }
    $path = urldecode($request['path']);
    if ($basePath != '/') {
      $l = strlen($basePath);
      if (substr($path, 0, $l) == $basePath) {
        $path = substr($path, $l);
      }
//       $path = str_replace($basePath, '', $path);
    }
    Logger::debug('Request for ' . $url . ' [' . $path . '] from ' . $this->ip);
    $path = explode('/', $path);
    $this->path = array();
    foreach ($path as $dir) {
      if ($dir != '') {
        $this->path[] = $dir;
      }
    }

    $this->realPath = $this->path;

    $this->query = $_GET;
    $this->data = $_POST;

    $this->cookies = new Cookies($_COOKIE, $sessionPrefix, $basePath);
    $this->session = new Session($sessionPrefix, $this->ip);
  }

  /**
   * Get value of property
   * @param string $name Property name
   * @return mixed Value of property
   */
  public function __get($name) {
    switch ($name) {
      case 'route':
      case 'path':
      case 'realPath':
      case 'data':
      case 'query':
      case 'cookies':
      case 'session':
      case 'fragment':
        return $this->$name;
      case 'ip':
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
      case 'url':
        return isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
      case 'referer':
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
      case 'userAgent':
        return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
    }
  }

  /**
   * Set value of property
   * @param string $name Name of property
   * @param string $value Value of property
   */
  public function __set($name, $value) {
    switch ($name) {
      case 'route':
      case 'path':
      case 'query':
      case 'fragment':
        $this->$name = $value;
    }
  }

  /**
   * Unset the entire GET query array or part of it
   * @param string $key A specific key to unset
   */
  public function unsetQuery($key = null) {
    if (!isset($key)) {
      $this->query = array();
    }
    else {
      unset($this->query[$key]);
    }
  }

  /**
   * Get the current access token or generate a new one
   * @return string Access token
   */
  public function getToken() {
    if (!isset($this->session['access_token'])) {
      $this->session['access_token'] = sha1(mt_rand());
    }
    return $this->session['access_token'];
  }

  /**
   * Compare the session access token with the POST'ed access token
   * @return bool True if they match, false otherwise
   */
  public function checkToken() {
    if (!isset($this->data['access_token'])
        OR !isset($this->session['access_token'])) {
      return false;
    }
    return $this->session['access_token'] === $this->data['access_token'];
  }

  /**
   * Whether or not the current request method is GET
   * @return bool True if GET, false if not
   */
  public function isGet() {
    return $_SERVER['REQUEST_METHOD'] == 'GET';
  }

  /**
   * Whether or not the current request method is POST
   * @return bool True if POST, false if not
   */
  public function isPost() {
    return $_SERVER['REQUEST_METHOD'] == 'POST';
  }

  /**
   * Whether or not the current request was made with AJAX
   * @return bool True if it is, false otherwise
   */
  public function isAjax() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        AND $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
  }
  
  /**
   * Whether or  not the current request was made by a mobile browser
   * @return boolean True if a mobile browser was detected, false otherwise
   */
  public function isMobile() {
    if (!isset($this->mobile)) {
      $agent = strtolower($this->userAgent);
      $this->mobile = false;
      if (isset($agent)) {
        if (strpos($agent, 'android') !== false
            OR strpos($agent, 'iphone') !== false
            OR strpos($agent, 'ipad') !== false
            OR strpos($agent, 'mobile') !== false // e.g. IEMobile
            OR strpos($agent, 'phone') !== false // e.g. Windows Phone OS
            OR strpos($agent, 'opera mini') !== false
            OR strpos($agent, 'maemo') !== false
            OR strpos($agent, 'blackberry') !== false
            OR strpos($agent, 'nokia') !== false
            OR strpos($agent, 'sonyericsson') !== false
            OR strpos($agent, 'opera mobi') !== false
            OR strpos($agent, 'symbos') !== false
            OR strpos($agent, 'symbianos') !== false
            OR strpos($agent, 'j2me') !== false) {
          $this->mobile = true;
        }
      }
    }
    return $this->mobile;
  }

}
