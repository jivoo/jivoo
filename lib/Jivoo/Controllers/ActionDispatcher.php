<?php
// Jivoo
// Copyright (c) 2015 Niels Sonnich Poulsen (http://nielssp.dk)
// Licensed under the MIT license.
// See the LICENSE file or http://opensource.org/licenses/MIT for more information.
namespace Jivoo\Controllers;

use Jivoo\Routing\IDispatcher;
use Jivoo\Routing\Routing;
use Jivoo\Routing\InvalidResponseException;
use Jivoo\Routing\Response;
use Jivoo\Routing\InvalidRouteException;
use Jivoo\Routing\RoutingTable;
use Jivoo\Core\Utilities;
use Jivoo\Core\Json;

/**
 * Action based routing.
 */
class ActionDispatcher implements IDispatcher {
  /**
   * @var Routing Routing module.
   */
  private $routing;
  
  /**
   * @var Controllers Controllers module;
   */
  private $controllers;
  
  /**
   * Construct url dispatcher.
   * @param Routing $routing Routing module.
   * @param Controllers $controllers Controllers module.
   */
  public function __construct(Routing $routing, Controllers $controllers) {
    $this->routing = $routing;
    $this->controllers = $controllers;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getPrefixes() {
    return array('action');
  }

  /**
   * {@inheritdoc}
   */
  public function validate(&$route) {
    if (isset($route['controller']) or isset($route['action'])) {
      if (!isset($route['controller'])) {
        $current = $this->routing->route;
        if (isset($current['controller']))
          $route['controller'] = $current['controller'];
      }
      if (!isset($route['parameters']))
        $route['parameters'] = array();
      return true;
    }
    return false;
  }

  /**
   * {@inheritdoc}
   */
  public function autoRoute(RoutingTable $table, $route, $resource = false) {
    $controller = $route['controller'];
    $dirs = explode('\\', $controller);
    if ($dirs == array('App'))
      $dirs = array();
    else
      $dirs = array_map(array('Jivoo\Core\Utilities', 'camelCaseToDashes'), $dirs);
    $patternBase = implode('/', $dirs);
    if ($resource) {
      $table->match($patternBase, 'action:' . $controller . '::index');
      $table->match($patternBase . '/add', 'action:' . $controller . '::add'); //C
      $table->match($patternBase . '/:0', 'action:' . $controller . '::view'); //R
      $table->match($patternBase . '/:0/edit', 'action:' . $controller . '::edit'); //U
      $table->match($patternBase . '/:0/delete', 'action:' . $controller . '::delete'); //D
      
      $table->match('DELETE ' . $patternBase . '/:0', 'action:' . $controller . '::delete');
      $table->match('PATCH ' . $patternBase . '/:0', 'action:' . $controller . '::edit');
      $table->match('PUT ' . $patternBase . '/:0', 'action:' . $controller . '::edit');
      $table->match('POST ' . $patternBase, 'action:' . $controller . '::add');
      return $patternBase . '/:0';
    }
    else {
      if (isset($route['action'])) {
        $action = $route['action'];
        $class = $this->controllers->getClass($controller);
        if (!$class) {
          throw new \Exception(tr('Invalid controller: %1', $controller));
        }
        $route = array(
          'controller' => $controller,
          'action' => $action
        );
        $reflect = new \ReflectionMethod($class, $action);
        $required = $reflect->getNumberOfRequiredParameters();
        $total = $reflect->getNumberOfParameters();
        if (!empty($prefix) AND substr($prefix, -1) != '/') {
          $prefix .= '/';
        }
        if ($action == 'index') {
          $table->match($patternBase, $route);
        }
        $patternBase .= '/' . Utilities::camelCaseToDashes($action);
        if ($required < 1) {
          $table->match($patternBase, $route);
        }
        $path = $patternBase;
        for ($i = 0; $i < $total; $i++) {
          $path .= '/*';
          if ($i <= $required) {
            $table->match($path, $route);
          }
        }
        return $patternBase;
      }
      else {
        $actions = $this->controllers->getActions($controller);
        if ($actions === false) {
          throw new \Exception(tr('Invalid controller: %1', $controller));
        }
        foreach ($actions as $action) {
          $route['action'] = $action;
          $this->autoRoute($table, $route, false);
        }
        return $patternBase;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function toRoute($routeString) {
    if (preg_match('/^action:(?:([a-z0-9_\\\\]+)::)?([a-z0-9_\\\\]+)(\[.*\])?$/i', $routeString, $matches) !== 1)
      throw new InvalidRouteException(tr('Invalid route string for action dispatcher'));
    $route = array(
      'parameters' => array()
    );
    if (isset($matches[3])) {
      $route['parameters'] = Json::decode($matches[3]);
      if (!is_array($route['parameters']))
        throw new InvalidRouteException(tr('Invalid JSON parameters in route string'));
    }
    if ($matches[1] != '') {
      $route['controller'] = $matches[1];
      $route['action'] = $matches[2];
    }
    else if (ucfirst($matches[2]) === $matches[2]) {
      $route['controller'] = $matches[2];
    }
    else {
      if (isset($this->routing->route['controller']))
        $route['controller'] = $this->routing->route['controller'];
      $route['action'] = $matches[2];
    }
    return $route;
  }

  /**
   * {@inheritdoc}
   */
  public function fromRoute($route) {
    if (!isset($route['action']))
      $route['action'] = 'index';
    return 'action:' . $route['controller'] . '::' . $route['action'];
  }
  
  /**
   * {@inheritdoc}
   */
  public function isCurrent($route) {
    if (!isset($route['action']))
      $route['action'] = 'index';
    $selection = $this->routing->route;
    if (!isset($selection['controller']))
      return false;
    return $selection['controller'] == $route['controller']
      and ($route['action'] == '*'
        or $selection['action'] == $route['action'])
      and ($route['parameters'] == '*'
        or $selection['parameters'] == $route['parameters']);
  }
  
  /**
   * {@inheritdoc}
   */
  public function getPath($route, $path = null) {
    if (!isset($path))
      return null;
    return Routing::insertParameters($route['parameters'], $path);
  }

  /**
   * {@inheritdoc}
   */
  public function createDispatch($route) {
    $controller = $this->controllers->getController($route['controller']);
    if (!isset($controller))
      throw new InvalidRouteException(tr('Invalid controller: %1', $route['controller']));
    if (!isset($route['action']))
      $route['action'] = 'index';
    if (!is_callable(array($controller, $route['action']))) {
      throw new InvalidRouteException(tr(
        'Invalid action: %1',
        $route['controller'] . '::' . $route['action']
      ));
    }
    return function() use($controller, $route) {
      $controller->before();
      $response = call_user_func_array(array(
        $controller, $route['action']), $route['parameters']
      );
      $controller->after($response);
      return $response;
    };
  }
}
