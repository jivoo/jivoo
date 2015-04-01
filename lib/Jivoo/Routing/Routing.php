<?php
// Jivoo
// Copyright (c) 2015 Niels Sonnich Poulsen (http://nielssp.dk)
// Licensed under the MIT license.
// See the LICENSE file or http://opensource.org/licenses/MIT for more information.
namespace Jivoo\Routing;

use Jivoo\Core\LoadableModule;
use Jivoo\Core\Event;
use Jivoo\Core\Utilities;
use Jivoo\Core\Logger;

/**
 * Module for handling routes and HTTP requests.
 * 
 * A "route" as a value is either an array, an {@see ILinkable} object,
 * a string or `null`.
 * 
 * The format of the array is:
 * <code>
 * array(
 *   'url' => ..., // a URL (should be absolute)
 *   'path' => ..., // a path array e.g. array('posts', '23') : /posts/23
 *   'query' => ..., // query array e.g. array('p' => '27') : ?p=27
 *   'mergeQuery' => ..., // boolean, whether to merge with current query
 *   'fragment' => ..., // fragment string e.g. 'bottom' : #bottom
 *   'controller' => ..., // Controller-object or name (w/ or w/o 'Controller'-suffix)
 *   'action' => ..., // name of action as string
 *   'parameters' => ... // array of parameters e.g. array(23, 1)
 * )
 * </code>
 * 
 * The array is validated before use by {@see Routing::validateRoute()}. The
 * following rules are used when converting a route to a link:
 * * If 'url' is set, the url is returned.
 * * If 'controller' isn't set, 'controller', 'action' and 'parameters' default
 *   to their current values.
 * * If 'controller' is set, 'action' defaults to 'index' and 'parameters'
 *   defaults to array().
 * * If 'path' is set, a link based on 'path', 'query' and 'fragment' is
 *   returned.
 * * If 'query' isn't set and 'controller', 'action' and 'parameters' are
 *   left unchanged, the 'query' is set to the current query.
 * 
 * Some examples:
 * * `array()`
 *   A link to the current page.
 * * `array('fragment' => 'test')`
 *   A link to the current page + the fragment #test.
 * * `array('controller' => 'Pages')`
 *   A link to the index-action of the PagesController.
 *   
 * Other legal values are:
 * * An object implementing {@see ILinkable}, in which case the
 *   {@see ILinkable::getRoute()} method is called
 * * `null`, in which case a link to the frontpage is returned
 * * A string, in which case the following grammar applies:
 * <code>
 * route      ::= url
 *             | {namespace "::"} controller ["::" action {"::" parameter}]
 * </code>
 * If the string contains at least one forward slash, it's interpretted as a
 * URL, and returned unchanged. If not it is interpretted as a combination of
 * controller name, action and parameters.
 * Controller namespace and controller names always begin with uppercase
 * characters. An example of a string would be 'Setup::Database::setup', in
 * which 'Setup' is a namespace, 'Database' is the controller and 'setup' is
 * the action. The resulting controller would be 'DatabaseSetupController'.
 * 
 * @property-read Request $request Current request.
 * @property-read array|ILinkable|string|null $route The currently selected
 * route, contains the current controller, action and parameters, see {@see Routing}.
 * @property-read array|ILinkable|string|null $root The root route, see {@see Routing}.
 * @property-read array|ILinkable|string|null $error The error route, see {@see Routing}.
 * @property-read DispatcherCollection $dispatchers Collection of dispatchers.
 * @property-read RoutingTable $routes Routing table.
 */
class Routing extends LoadableModule {
  /**
   * @var DispatcherCollection Collection of dispatchers.
   */
  private $dispatchers;
  
  /**
   * @var RoutingTable Table of routes;
   */
  private $routes;
  
  /**
   * @var string[] Paths;
   */
  private $paths;
  
  /**
   * @var array Root route and priority
   */
  private $root = null;
  
  /**
   * @var mixed Error route
   */
  private $error = null;

  /**
   * @var array Selected route and priority
   */
  private $selection = null;

  /**
   * @var bool Whether or not the page has rendered yet
   */
  private $rendered = false;

  /**
   * @var bool Use etags.
   */
  private $etags = false;

  /**
   * {@inheritdoc}
   */
  protected $events = array('beforeLoadRoutes', 'afterLoadRoutes', 'beforeFindRoute', 'beforeRender', 'afterRender', 'beforeRedirect', 'beforeCallAction', 'afterCallAction');

  /**
   * {@inheritdoc}
   */
  protected function init() {
    // Set default settings
    $this->config->defaults = array(
      'rewrite' => false,
      'sessionPrefix' => $this->app->sessionPrefix,
    );

    $this->request = new Request($this->config['sessionPrefix'], $this->app->basePath);
    
    $this->dispatchers = new DispatcherCollection($this);
    $this->dispatchers->add(new PathDispatcher($this));
    $this->dispatchers->add(new UrlDispatcher($this));
    
    $this->routes = new RoutingTable($this);

    // Determine if the current URL is correct
    if ($this->config['rewrite']) {
      if (isset($this->request->path[0]) AND $this->request->path[0] == $this->app->entryScript) {;
        if (count($this->request->path) <= 1) {
          $this->redirectPath(array(), $this->request->query);
        }
        else {
          $this->request->path = array_slice($this->request->path, 1);
          $this->redirectPath($this->request->path, $this->request->query);
        }
      }
    }
    else {
      if (!isset($this->request->path[0]) OR $this->request->path[0] != $this->app->entryScript) {
        $this->redirectPath($this->request->path, $this->request->query);
      }
      $path = $this->request->path;
      array_shift($path);
      $this->request->path = $path;
    }

    if (isset($this->config['root'])) {
      $this->setRoot($this->config['root'], 10);
    }

    $this->app->attachEventHandler('afterLoadModules', array($this, 'loadRoutes'));
    $this->app->attachEventHandler('afterInit', array($this, 'findRoute'));
  }
  
  /**
   * {@inheritdoc}
   */
  public function __get($property) {
    switch ($property) {
      case 'request':
      case 'dispatchers':
      case 'routes':
      case 'root':
      case 'error':
        return $this->$property;
      case 'route':
        return $this->selection;
    }
    return parent::__get($property);
  }

  /**
   * {@inheritdoc}
   */
  public function __isset($property) {
    switch ($property) {
      case 'request':
      case 'dispatchers':
      case 'routes':
      case 'root':
      case 'error':
        return isset($this->$property);
      case 'route':
        return isset($this->selection);
    }
    return parent::__isset($property);
  }
  
  /**
   * Load routes from routing configuration file.
   */
  public function loadRoutes() {
    $this->triggerEvent('beforeLoadRoutes');
    $routesFile = $this->p('app', 'config/routes.php');
    if (file_exists($routesFile)) {
      $routes = $this->routes;
      $routes->load($routesFile);
    }
    $this->triggerEvent('afterLoadRoutes');
  }
  
  /**
   * Set current route.
   * @param array|ILinkable|string|null $route A route, see {@see Routing}.
   * @param number $priority Priority of route.
   */
  public function setRoot($route, $priority = 9) {
    if (isset($this->root) and $priority <= $this->root['priority'])
      return;
    $route = $this->validateRoute($route);
    $route['priority'] = $priority;
    $this->root = $route;
    if (isset($route['path'])) {
      if (count($this->request->path) === 0) {
        $this->request->path = $route['path'];
        $this->request->query = array_merge($route['query'], $this->request->query);
      }
      else if ($route['path'] == $this->request->path) {
        $this->redirectPath(array(), $this->request->query);
      }
    }
    else {
      $this->addPath($route, array(), 0, $priority);
      if (count($this->request->path) === 0) {
        $this->setRoute($route, $priority);
      }
    }
  }
  
  /**
   * Set error route.
   * @param array|ILinkable|string|null $route A route, see {@see Routing}.
   */
  public function setError($route) {
    $this->error = $route;
    $this->setRoute($route, 1);
  }

  /**
   * Will replace **, :*, * and :n in path with parameters.
   * @param mixed[] $parameters Parameters list.
   * @param string[] $path Path array.
   * @return string[] Resulting path.
   */
  public static function insertParameters($parameters, $path) {
    $result = array();
    foreach ($path as $part) {
      if ($part == '**' || $part == ':*') {
        while (current($parameters) !== false) {
          $result[] = array_shift($parameters);
        }
        break;
      }
      else if ($part == '*') {
        $part = array_shift($parameters);
      }
      else if ($part[0] == ':') {
        $var = substr($part, 1);
        if (is_numeric($var)) {
          $offset = (int)$var;
          $part = $parameters[$offset];
          unset($parameters[$offset]);
        }
        else {
          $part = $parameters[$var];
        }
      }
      $result[] = $part;
    }
    return $result;
  }

  /**
   * Check whether or not a route matches the current request.
   * @param array|ILinkable|string|null $route A route, see {@see Routing}.
   * @throws InvalidRouteException If route is not valid.
   * @return boolean True if current route, false otherwise.
   */
  public function isCurrent($route = null, $defaultAction = 'index', $defaultParameters = array()) {
    $route = $this->validateRoute($route, $defaultAction, $defaultParameters);
    return $route['dispatcher']->isCurrent($route);
  }
  
  /**
   * Get a URL from a path, query and fragment.
   * @param string[] $path Path as array.
   * @param array $query GET query.
   * @param string $fragment Fragment.
   * @param string $rewrite If true 'index.php/' will not be included in link.
   * @return string A URL.
   */
  public function getLinkFromPath($path = null, $query = null, $fragment = null,
    $rewrite = false) {
    if (!isset($path)) {
      $path = $this->request->path;
    }
    if (isset($fragment)) {
      $fragment = '#' . $fragment;
    }
    else {
      $fragment = '';
    }
    if (is_array($query) and count($query) > 0) {
      $queryStrings = array();
      foreach ($query as $key => $value) {
        if ($value === '') {
          $queryStrings[] = urlencode($key);
        }
        else {
          $queryStrings[] = urlencode($key) . '=' . urlencode($value);
        }
      }
      $combined = implode('/', $path) . '?' . implode('&', $queryStrings) .
                   $fragment;
      if ($this->config['rewrite'] OR $rewrite) {
        return $this->w($combined);
      }
      else {
        return $this->w($this->app->entryScript . '/' . $combined);
      }
    }
    else {
      if ($this->config['rewrite'] OR $rewrite) {
        return $this->w(implode('/', $path) . $fragment);
      }
      else {
        return $this->w($this->app->entryScript . '/' . implode('/', $path) . $fragment);
      }
    }
  }
  
  /**
   * Merge two routes.
   * @param array|ILinkable|string|null $route A route, see {@see Routing}.
   * @param array $mergeWith Route array to merge with.
   * @param array Resulting route (as an array).
   */
  public function mergeRoutes($route = null, $mergeWith = array()) {
    $route = $this->validateRoute($route);
    return array_merge($route, $mergeWith);
  }

  /**
   * Validate route.
   * @param array|ILinkable|string|null $route A route, see {@see Routing}.
   * @throws InvalidRouteException If invalid route.
   * @return array A valid route array.
   */
  public function validateRoute($route) {
    return $this->dispatchers->validate($route);
  }
  
  /**
   * Get a URL for a route (including http://domain.name).
   * @param array|ILinkable|string|null $route A route, see {@see Routing}.
   * @throws InvalidRouteException If incomplete route.
   * @return string A URL.
   */
  public function getUrl($route = null) {
    $link = $this->getLink($route);
    if (strpos($link, '://') !== false)
      return $link;
    return $this->request->domainName . $link;
  }
  
  /**
   * Get a link for a route (absolute path).
   * @param array|ILinkable|string|null $route A route, see {@see Routing}.
   * @throws InvalidRouteException If no path found.
   * @return string[] A path array.
   */
  public function getPath($route = null) {
    $route = $this->validateRoute($route);
    $arity = '[' . count($route['parameters']) . ']';
    $routeString = $route['dispatcher']->fromRoute($route);
    $path = null;
    if (isset($this->paths[$routeString . $arity])) {
      $path = $this->paths[$routeString . $arity]['pattern'];
    }
    else if (isset($this->paths[$routeString . '[*]'])) {
      $path = $this->paths[$routeString . '[*]']['pattern'];
    }
    $path = $route['dispatcher']->getPath($route, $path);
    if (!isset($path))
      throw new InvalidRouteException(tr('Could not find path for ' . $routeString . $arity));
    return $path;
  }
  
  /**
   * Get a link for a route (absolute path).
   * @param array|ILinkable|string|null $route A route, see {@see Routing}.
   * @throws InvalidRouteException If no path found.
   * @return string A link.
   */
  public function getLink($route = null) {
    $route = $this->validateRoute($route);
    if (isset($route['parameters']))
      $arity = '[' . count($route['parameters']) . ']';
    else
      $arity = '[0]';
    $routeString = $route['dispatcher']->fromRoute($route);
    $path = null;
    if (isset($this->paths[$routeString . $arity])) {
      $path = $this->paths[$routeString . $arity]['pattern'];
    }
    else if (isset($this->paths[$routeString . '[*]'])) {
      $path = $this->paths[$routeString . '[*]']['pattern'];
    }
    $path = $route['dispatcher']->getPath($route, $path);
    if (!isset($path))
      throw new InvalidRouteException(tr('Could not find path for ' . $routeString . $arity));
    if (is_string($path))
      return $path;
    return $this->getLinkFromPath($path, $route['query'], $route['fragment']);
  }
  
  /**
   * Perform a redirect.
   * @param string[] $path Path array.
   * @param array $query GET query.
   * @param string $moved Whether or not to use a 301 status code.
   * @param string $fragment Fragment.
   * @param string $rewrite If true 'index.php/' will not be included.
   */
  public function redirectPath($path = null, $query = null, $moved = true,
    $fragment = null, $rewrite = false) {
    $status = $moved ? Http::MOVED_PERMANENTLY : Http::SEE_OTHER;
    Http::redirect($status, $this->getLinkFromPath($path, $query, $fragment));
  }

  /**
   * Perform a redirect.
   * @param array|ILinkable|string|null $route A route, see {@see Routing}.
   */
  public function redirect($route = null) {
    $this->triggerEvent('beforeRedirect', new RedirectEvent($this, $route, false));
    Http::redirect(Http::SEE_OTHER, $this->getLink($route));
  }

  /**
   * Perform a permanent redirect.
   * @param array|ILinkable|string|null $route A route, see {@see Routing}.
   */
  public function moved($route = null) {
    $this->triggerEvent('beforeRedirect', new RedirectEvent($this, $route, true));
    Http::redirect(Http::MOVED_PERMANENTLY, $this->getLink($route));
  }

  /**
   * Refresh current page.
   * @param array $query GET query, default is current.
   * @param string $fragment Fragment, default is none.
   */
  public function refresh($query = null, $fragment = null) {
    if (!isset($query)) {
      $query = $this->request->query;
    }
    $this->redirectPath($this->request->path, $query, false, $fragment);
  }
  
  /**
   * Automatically create routes.
   * @param array|ILinkable|string|null $route A route, see {@see Routing}.
   * @param bool $resource Whether to use resource routing.
   */
  public function autoRoute($route, $resource = false) {
    $route = $this->validateRoute($route);
    $route['dispatcher']->autoRoute($this->routes, $route, $resource);
  }
  
  /**
   * Add a route/path combination. Set route if pattern matches current
   * path.
   * 
   * A pattern is a path such as 'admin/login'. Different placeholders can be
   * used:
   * * A '\*' can be used instead of a parameter. For instance if the path
   *   'users/view/\*' is pointed at the UsersController and the view-action,
   *   any string can be used in place of the asterisk, and the value will be
   *   used as a parameter for the view-action. Multiple asterisks will be used
   *   as multiple parameters.
   * * A colon ':' followed by a number refers to a specific parameter, starting
   *   from 0. The same example as above with a numbered parameter would be
   *   'users/view/:0'.
   * * The placeholders '**' and ':*' are identical, and results in the rest
   *   of the path being put into parameters.
   * * The placeholder ':controller' will set the controller, and ':action' will
   *   set the action. 
   *
   * @param string $pattern A path pattern.
   * @param array|ILinkable|string|null $route A route, see {@see Routing}.
   * @param number $priority Priority of route.
   * @throws InvalidRouteException If unknown placeholder.
   */
  public function addRoute($pattern, $route, $priority = 5) {
    $route = $this->validateRoute($route);
    $route['priority'] = $priority;

    $pattern = explode(' ', $pattern, 2);
    $method = 'ANY';
    if (count($pattern) > 1) {
      $method = strtoupper($pattern[0]);
      $pattern = $pattern[1];
    }
    else {
      $pattern = $pattern[0];
    }
    $pattern = trim($pattern, '/');
    $pattern = explode('/', $pattern);
    
    $path = $this->request->path;
    $arity = 0;
    foreach ($pattern as $part) {
      if ($part == '**' || $part == ':*') {
        $arity = '*';
        break;
      }
      else if ($part == '*') {
        $arity++;
      }
      else if ($part[0] == ':') {
        $var = substr($part, 1);
        if (is_numeric($var)) {
          $arity++;
        }
      }
    }
    $isMatch = true;
    $patternc = count($pattern);
    if ($method != 'ANY' && $method != $this->request->method) {
      $isMatch = false;
    }
    else if ($patternc < count($path) AND $pattern[$patternc - 1] != '**'
      AND $pattern[$patternc - 1] != ':*') {
      $isMatch = false;
    }
    else {
      foreach ($pattern as $j => $part) {
        if ($part == '**' || $part == ':*') {
          $route['parameters'] = array_merge(
            $route['parameters'],
            array_slice($path, $j)
          );
          $arity = '*';
          break;
        }
        else if (!isset($path[$j])) {
          $isMatch = false;
          break;
        }
        if ($path[$j] == $part) {
          continue;
        }
        if ($part == '*') {
          $route['parameters'][] = $path[$j];
          continue;
        }
        if ($part[0] == ':') {
          $var = substr($part, 1);
          if (is_numeric($var)) {
            $route['parameters'][(int)$var] = $path[$j];
          }
          else {
            $route['parameters'][$var] = $path[$j];
          }
          continue;
        }
        $isMatch = false;
        break;
      }
    }
    if ($isMatch) {
      if ($priority > $this->selection['priority']) { // or >= ??
        $this->selection = $route;
      }
    }
    $this->addPath($route, $pattern, $arity, $priority);
  }

  /**
   * Add association of route and path-pattern.
   * 
   * @param array|ILinkable|string|null $route A route, see {@see Routing}.
   * @param string[] $pattern A pattern array.
   * @param int|string $arity Arity of pattern (integer or '*').
   * @param int $priority Priority of path.
   * @return bool True if pattern added, false otherwise.
   */
  public function addPath($route, $pattern, $arity, $priority = 5) {
    $route = $this->validateRoute($route);
    $key = $route['dispatcher']->fromRoute($route) . '[' . $arity . ']';
    if (isset($this->paths[$key])) {
      if ($priority <= $this->paths[$key]['priority'])
        return false;
    }
    $this->paths[$key] = array(
      'pattern' => $pattern,
      'priority' => $priority
    );
    return true;
  }

  /**
   * Set current route.
   * @param array|ILinkable|string|null $route A route, see {@see Routing}.
   * @param int $priority Priority of route.
   * @return boolean True if successful, false if a route with higher priority
   * was previously set.
   */
  public function setRoute($route, $priority = 7) {
    if ($this->rendered) {
      return false;
    }
    $route = $this->validateRoute($route);
    if (!isset($this->selection) or $priority > $this->selection['priority']) {
      $this->selection = $route;
      $this->selection['priority'] = $priority;
      return true;
    }
    return false;
  }
  
  /**
   * Find the best route and dispatch.
   * @throws InvalidRouteException If no route selected.
   */
  public function findRoute() {
    $this->triggerEvent('beforeFindRoute');

    if (!isset($this->selection)) {
      throw new InvalidRouteException(tr('No route selected'));
    }
    
    $this->followRoute($this->selection); 
  }

  /**
   * Follow a route.
   * @param array|ILinkable|string|null $route A route, see {@see Routing}.
   * @throws InvalidRouteException If route is invalid.
   */
  public function followRoute($route) {
    $route = $this->validateRoute($route);
    $event = new RenderEvent($this, $route);
    $this->triggerEvent('beforeFollowRoute', $event);
    
    if ($this->request->path != array() and isset($this->root) and
         $this->isCurrent($this->root)) {
      $this->redirectPath(array(), $this->request->query);
    }
    
    if (isset($route['query'])) {
      $this->request->query = array_merge($route['query'], $this->request->query);
    }
    
    $this->selection = $route;
    
    $this->rendered = true;
    $this->request->route = $route;
    try {
      $response = $route['dispatcher']->dispatch($route);
      
      if (is_string($response))
        $response = new TextResponse(Http::OK, 'text', $response);
      if (!($response instanceof Response)) {
        throw new InvalidResponseException(tr(
          'An invalid response was returned'
        ));
      }
    }
    catch (ResponseOverrideException $e) {
      $response = $e->getResponse();
    }
    catch (NotFoundException $e) {
      return $this->followRoute($this->error);
    }
    $event->response = $response;
    $this->triggerEvent('afterFollowRoute', $event);
    $this->respond($response);
  }

  /**
   * Sends a response to the client and stops execution of the applicaton.
   * @param Response $response Response object.
   */
  public function respond(Response $response) {
    if (headers_sent($file, $line))
      throw new \Exception(tr('Headers already sent in %1 on line %2', $file, $line));
    $event = new RenderEvent($this, $this->selection, $response);
    $this->triggerEvent('beforeRender', $event);
    Http::setStatus($response->status);
    Http::setContentType($response->type);
    if (isset($response->modified)) {
      header('Modified: ' . Http::date($response->modified));
    }
    if (isset($response->cache)) {
      $cache = $response->cache;
      if (isset($response->maxAge)) {
        $cache .= ', max-age=' . $response->maxAge;
        header('Expires: ' . Http::date(time() + $response->maxAge));
      }
      header('Pragma: ' . $response->cache);
      header('Cache-Control: ' . $cache);
    }
    else if ($this->etags) {
      $tag = md5($response->body);
      header('ETag: ' . $tag);
      header('Cache-Control: must-revalidate');
      header('Pragma: must-revalidate');
      if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
        $tags = explode(',', $_SERVER['HTTP_IF_NONE_MATCH']);
        foreach ($tags as $match) {
          if (trim($match) == $tag) {
            Http::setStatus(Http::NOT_MODIFIED);
            $this->app->stop();
          }
        }
      }
    }
    $body = $response->body;
    $event->body = $body;
    $this->triggerEvent('afterRender', $event);
    if ($event->overrideBody)
      $body = $event->body;
    if (function_exists('bzcompress') and $this->request->acceptsEncoding('bzip2')) {
      header('Content-Encoding: bzip2');
      echo bzcompress($body);
    }
    else if (function_exists('gzencode') and $this->request->acceptsEncoding('gzip')) {
      header('Content-Encoding: gzip');
      echo gzencode($body);
    }
    else {
      echo $body;
    }
    $this->app->stop();
  }

  /**
   * Make sure that the current path matches the controller and action. If not,
   * redirect to the right path.
   * @param array|ILinkable|string|null $route A route, see {@see Routing}.
   */
  public function reroute($route = null) {
    $currentPath = $this->request->path;
    $path = $this->getPath($route);
    if ($currentPath != $path) {
      $this->redirectPath($path, $this->request->query);
    }
  }
}

/**
 * Invalid route.
 */
class InvalidRouteException extends \Exception { }

/**
 * Invalid response.
 */
class InvalidResponseException extends \Exception { }

/**
 * Can be used in an action to send the client to the error page.
 */
class NotFoundException extends \Exception { }

/**
 * When thrown, the current response is replaced.
 */
class ResponseOverrideException extends \Exception {
  /**
   * @var Response New response object.
   */
  private $response;

  /**
   * Construct response override.
   * @param Response $response New response object.
   */
  function __construct(Response $response) {
    $this->response = $response;
  }

  /**
   * Get the response object.
   * @return Response Response object.
   */
  function getResponse() {
    return $this->response;
  }
}

/**
 * The event of rendering a page.
 */
class RenderEvent extends Event {
  /**
   * @var array|ILinkable|string|null $route The route being followed, see {@see Routing}.
   */
  public $route;
  
  /**
   * @var Response|null The rendered response if any.
   */
  public $response;
  
  /**
   * @var string|null The response body if any.
   */
  public $body;
  
  /**
   * @var bool Set to true to override response body.
   */
  public $overrideBody = false;
  
  /**
   * Construct render event.
   * @param object $sender Sender object.
   * @param array|ILinkable|string|null $route The route being followed, see {@see Routing}.
   * @param Response|null The rendered response if any.
   * @param string|null The response body if any.
   */
  public function __construct($sender, $route, Response $response = null, $body = null) {
    parent::__construct($sender);
    $this->route = $route;
    $this->response = $response;
    $this->body = $body;
  }
}

/**
 * The event of calling an action.
 */
class CallActionEvent extends Event {
  /**
   * @var Controller The controller.
   */
  public $controller;
  
  /**
   * @var string The name of the action.
   */
  public $action;
  
  /**
   * @var Response|null Response returned by action if any.
   */
  public $response;
  
  /**
   * Construct call action event.
   * @param object $sender Sender object.
   * @param Controller $controller The controller.
   * @param string $action Name of the action.
   * @param string[] $parameters Parameters for action.
   * @param Response|null $response Response returned by action if any.
   */
  public function __construct($sender, Controller $controller, $action, $parameters, Response $response = null) {
    parent::__construct($sender, $parameters);
    $this->controller = $controller;
    $this->action = $action;
    $this->response = $response;
  }
}

/**
 * A redirect event.
 */
class RedirectEvent extends Event {
  /**
   * @var array|ILinkable|string|null $route A route, see {@see Routing}.
   */
  public $route;
  
  /**
   * @var bool Whether it is a permanent (true) or temporary (false) redirect.
   */
  public $moved;
  
  /**
   * Construct redirect event.
   * @param object $sender Sender object.
   * @param array|ILinkable|string|null $route A route, see {@see Routing}.
   * @param bool $movied Whether it is a permanent (true) or temporary (false) redirect.
   */
  public function __construct($sender, $route, $moved) {
    parent::__construct($sender);
    $this->route = $route;
    $this->moved = $moved;
  }
}
