<?php
// Jivoo
// Copyright (c) 2015 Niels Sonnich Poulsen (http://nielssp.dk)
// Licensed under the MIT license.
// See the LICENSE file or http://opensource.org/licenses/MIT for more information.
namespace Jivoo\Snippets;

use Jivoo\Core\Module;
use Jivoo\Core\App;
use Jivoo\Routing\NotFoundException;
use Jivoo\Core\Utilities;
use Jivoo\View\ViewResponse;
use Jivoo\Core\Lib;

/**
 * A loadable snippet.
 */
class Snippet extends Module implements ISnippet {
  /**
   * @var string[] A list of other helpers needed by this helper.
  */
  protected $helpers = array('Html');
  
  /**
   * @var string[] A list of models needed by this helper.
  */
  protected $models = array();
  
  /**
   * @var string[] Names of parameters required by this snippet.
   */
  protected $parameters = array();
  
  /**
   * @var string Data key.
   */
  protected $dataKey = null;
  
  /**
   * @var mixed[] Values of required parameters.
   */
  private $parameterValues = array();
  
  /**
   * @var Helper[] An associative array of helper names and objects.
  */
  private $helperObjects = array();
  
  /**
   * @var IBasicModel[] An associative array of model names and objects.
  */
  private $modelObjects = array();
  
  /**
   * @var int HTTP status code.
   */
  private $status = 200;
  
  /**
   * @var bool Whether or not to render the layout.
   */
  private $enableLayout = false;
  
  /**
   * @var array Data for template.
   */
  protected $viewData = array();
  
  /**
   * Construct snippet.
   */
  public final function __construct(App $app) {
    $this->inheritElements('modules');
    $this->inheritElements('helpers');
    $this->inheritElements('models');
    parent::__construct($app);
    
//     if (!isset($this->dataKey))
//       $this->dataKey = Lib::getClassName($this);
    
    if (isset($this->m->Helpers)) {
      $this->helperObjects = $this->m->Helpers->getHelpers($this->helpers);
      foreach ($this->helperObjects as $name => $helper) {
        $this->view->data->$name = $helper;
      }
    }
    if (isset($this->m->Models)) {
      $this->modelObjects = $this->m->Models->getModels($this->models);
    }
    $this->init();
  }
  
  /**
   * Snippet initialization, called by constructor.
   */
  protected function init() { }

  /**
   * Get an associated model, helper or data-value (in that order).
   * @param string $name Name of model/helper or key for data-value.
   * @return Model|Helper|mixed Associated value.
   */
  public function __get($name) {
    if (isset($this->modelObjects[$name])) {
      return $this->modelObjects[$name];
    }
    if (isset($this->helperObjects[$name])) {
      return $this->helperObjects[$name];
    }
    if (array_key_exists($name, $this->parameterValues))
      return $this->parameterValues[$name];
    return parent::__get($name);
  }
  
  /**
   * {@inheritdoc}
   */
  public function __isset($name) {
    if (isset($this->modelObjects[$name]))
      return true;
    return isset($this->parameterValues[$name]);
  }
  
  /**
   * Called before invoking.
   * @return Response|string|null If a response or a string is returned, snippet
   * execution ends.
   */
  public function before() {
    return null;
  }
  
  /**
   * Called after invoking.
   * @param Response|string $response Respone object.
   */
  public function after($response) {
    return $response;
  }

  /**
   * Respond to a GET request.
   * @return Response|string A response object or content.
   */
  public function get() {
    return $this->render();
  }

  /**
   * Respond to a POST request.
   * @param array $data POST data.
   * @return Response|string A response object or content.
   */
  public function post($data) {
    return $this->get();
  }

  /**
   * Respond to a PUT request.
   * @param array $data PUT data.
   * @return Response|string A response object or content.
   */
  public function put($data) {
    return $this->get();
  }

  /**
   * Respond to a PATCH request.
   * @param array $data PATCH data.
   * @return Response|string A response object or content.
   */
  public function patch($data) {
    return $this->get();
  }

  /**
   * Respond to a GET request.
   * @return Response|string A response object or content.
   */
  public function delete() {
    return $this->get();
  }
  
  /**
   * {@inheritdoc}
   */
  public function __invoke($parameters = array()) {
    $this->parameterValues = array();
    foreach ($this->parameters as $offset => $name) {
      if (isset($parameters[$name]))
        $this->parameterValues[$name] = $parameters[$name];
      else if (isset($parameters[$offset]))
        $this->parameterValues[$name] = $parameters[$offset];
      else
        $this->parameterValues[$name] = null;
    }
    $before = $this->before();
    if (isset($before))
      return $this->after($before);
    if ($this->request->isGet())
      return $this->after($this->get());
    if (!$this->request->hasValidData($this->dataKey))
      return $this->after($this->get());
    if (isset($this->dataKey))
      $data = $this->request->data[$this->dataKey];
    else
      $data = $this->request->data;
    switch ($this->request->method) {
      case 'POST':
        return $this->after($this->post($data));
      case 'PUT':
        return $this->after($this->put($data));
      case 'PATCH':
        return $this->after($this->patch($data));
      case 'DELETE':
        return $this->after($this->delete());
    }
    return $this->after($this->invalid());
  }

  /**
   * Redirect to a route.
   * @param array|ILinkable|string|null $route A route, see {@see Routing}.
   */
  protected function redirect($route = null) {
    $this->m->Routing->redirect($route);
  }

  /**
   * Refresh the current path with optional query data and fragment.
   * @param array $query Associative array of query data.
   * @param string $fragment Fragment of page.
   */
  protected function refresh($query = null, $fragment = null) {
    $this->m->Routing->refresh($query, $fragment);
  }

  /**
   * Set HTTP status code, e.g. 200 for OK or 404 for file not found.
   * @param integer $httpStatus HTTP status code.
   */
  protected function setStatus($httpStatus) {
    $this->status = $httpStatus;
  }
  
  /**
   * Get HTTP status code.
   * @return integer HTTP status code.
   */
  public function getStatus() {
    return $this->status;
  }
  
  /**
   * Enable layout for snippet. Will be disabled automatically after next call
   * to {@see render()}.
   * @param string $enable Enable layout.
   */
  public function enableLayout($enable = true) {
    $this->enableLayout = $enable;
  }
  
  /**
   * Disable layout for snippet.
   */
  public function disableLayout() {
    $this->enableLayout = false;
  }
  
  /**
   * Call when request is invalid.
   * @return Response|string A response object or content.
   */
  protected function invalid() {
    throw new NotFoundException(tr('Invalid request.'));
  }

  /**
   * Render a template.
   *
   * If $templateName is not set, the path of the template will be computed
   * based on the name of the snippet.
   *
   * @param string $templateName Name of template to render.
   * @return string Rendered template.
   */
  protected function render($templateName = null) {
    if (!isset($templateName)) {
      $class = str_replace($this->app->n('Snippets\\'), '', get_class($this));
      $dirs = array_map(array('Jivoo\Core\Utilities', 'camelCaseToDashes'), explode('\\', $class));
      $templateName = implode('/', $dirs) . '.html';
    }
    $enableLayout = $this->enableLayout;
    $this->disableLayout();
    return $this->view->render($templateName, $this->viewData, $enableLayout);
  }
}