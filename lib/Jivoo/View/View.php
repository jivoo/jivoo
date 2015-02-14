<?php
// Jivoo
// Copyright (c) 2015 Niels Sonnich Poulsen (http://nielssp.dk)
// Licensed under the MIT license.
// See the LICENSE file or http://opensource.org/licenses/MIT for more information.
namespace Jivoo\View;

use Jivoo\Core\LoadableModule;

/**
 * The view module.
 * @property-read ViewData $data View data.
 * @property-read ViewResource $resources View resources.
 * @property-read ViewExtensions $extensions View extensions.
 * @property-read Template|null $template Template system if it has been
 * initialized.
 * @property-read ViewBlocks $blocks View blocks.
 * @method string link(array|ILinkable|string|null $route = null) Alias for
 * {@see Routing::getLink}. 
 * @method string url(array|ILinkable|string|null $route = null) Alias for
 * {@see Routing::getUrl}. 
 * @method bool isCurrent(array|ILinkable|string|null $route = null,
 *       string $defaultAction = 'index', array $defaultParameters = array())
 *  Alias for {@see Routing::isCurrent}.
 * @method array mergeRoutes(array|ILinkable|string|null $route = null,
 *       array $mergeWith = array()) Alias for {@see Routing::mergeRoutes}.
 * @method string file(string $file) Alias for {@see Assets::getAsset}.
 * @method icon(string $icon) Alias for {@see ViewBlocks::icon}.
 * @method meta(string $name, string $content) Alias for {@see ViewBlocks::meta}.
 * @method relation(string $rel, string $type, string $href) Alias for {@see ViewBlocks::relation}.
 * @method string block(string $name, string $default = '') Alias for {@see ViewBlocks::block}.
 * @method bool isEmpty(string $block) Alias for {@see ViewBlocks::isEmpty}.
 * @method provide(string $resource, string $location, string[] $dependencies = array(), string $condition = null)
 *  Alias for {@see ViewResources::provide}.
 * @method import(string $resource) Alias for {@see ViewResources::import}.
 * @method importConditional(string $resource, string $condition)
 *  Alias for {@see ViewResources::importConditional}.
 * @method string resourceBlock() Alias for {@see ViewResources::resourceBlock}.
 * @method IViewExtension[] extensions(string $hook = null, string $type = 'IViewExtension')
 *  Alias for {@see ViewExtensions::extensions}.
 */
class View extends LoadableModule {
  /**
   * {@inheritdoc}
   */
  protected $modules = array('Routing', 'Assets');
  
  /**
   * @var ViewData Data for view.
   */
  private $data;
  
  /**
   * @var ViewExtensions Collection of view extensions.
   */
  private $extensions;
  
  /**
   * @var ViewBlocks Collection of view blocks.
   */
  private $blocks;
  
  /**
   * @var Template Template system.
   */
  private $template = null;
  
  /**
   * @var callback[] Associative array mapping function names to callbacks.
   */
  private $functions = array();
  
  /**
   * @var ViewResources Collection of view resources.
   */
  private $resources = null;
  
  /**
   * @var int[] Associative array mapping paths to priorities.
   */
  private $templateDirs = array();
  
  /**
   * {@inheritdoc}
   */
  protected function init() {
    $this->resources = new ViewResources($this->m->Assets);
    $this->extensions = new ViewExtensions($this);
    $this->data = new ViewData();
    $this->blocks = new ViewBlocks($this);
    
    $this->addTemplateDir($this->p('app', 'templates'));

    $this->addFunction('link', array($this->m->Routing, 'getLink'));
    $this->addFunction('url', array($this->m->Routing, 'getUrl'));
    $this->addFunction('isCurrent', array($this->m->Routing, 'isCurrent'));
    $this->addFunction('mergeRoutes', array($this->m->Routing, 'mergeRoutes'));
    $this->addFunction('file', array($this->m->Assets, 'getAsset'));

    $this->addFunctions(
      $this->blocks,
      array('icon', 'meta', 'relation', 'block', 'isEmpty')
    );
    $this->addFunctions(
      $this->resources,
      array('provide', 'import', 'resourceBlock', 'importConditional')
    );
    $this->addFunctions(
      $this->extensions,
      array('extensions')
    );
  }
  
  /**
   * {@inheritdoc}
   */
  public function __get($property) {
    switch ($property) {
      case 'data':
      case 'blocks':
      case 'resources':
      case 'template':
      case 'extensions':
        return $this->$property;
    }
    return parent::__get($property);
  }

  /**
   * {@inheritdoc}
   */
  public function __call($function, $parameters) {
    if (isset($this->functions[$function]))
      return call_user_func_array($this->functions[$function], $parameters);
    return parent::__call($function, $parameters);
  }
  
  /**
   * Add a view function. Function can be called from templates.
   * @param string $name Function name.
   * @param callback $callback Callback for function.
   */
  public function addFunction($name, $callback) {
    $this->functions[$name] = $callback;
  }
  
  /**
   * Add several methods of an object as view functions.
   * @param object $object An object.
   * @param string[] $methods List of method names.
   */
  public function addFunctions($object, $methods) {
    foreach ($methods as $method)
      $this->functions[$method] = array($object, $method);
  }
  
  /**
   * Add a template directory.
   * @param string $dir Absolute path to directory.
   * @param int $priority Priority.
   */
  public function addTemplateDir($dir, $priority = 5) {
    $this->templateDirs[$dir] = $priority;
  }
  
  /**
   * Find template in available template directories.
   * @param string $template Template.
   * @return string|false $path Absolute path to template or false if not found.
   */
  public function findTemplate($template) {
    if (file_exists($template))
      return $template;
    foreach ($this->templateDirs as $dir => $priority) {
      if (substr($dir, -1, 1) != '/') {
        $dir .= '/';
      }
      $path = $dir . $template . '.php';
      if (file_exists($path)) {
        return $path;
      }
    }
    return false;
  }
  
  /**
   * Render a template.
   * @param string $template Template name.
   * @param array $data Addtional data for template.
   * @return string Content of template.
   */
  public function render($template, $data = array()) {
    if (isset($this->template))
      return $this->template->render($template, $data);
    arsort($this->templateDirs);
    $this->data->flash = $this->request->session->flash;
    // TODO: Um, why instantiate on every render?
    $this->template = new Template($this);
    $result = $this->template->render($template, $data);
    $this->template = null;
    return $result;
  }
  
  /**
   * Render a template without layout or parent templates.
   * @param string $template Template name.
   * @param array $data Additional data for template.
   * @return string Content of template.
   */
  public function renderOnly($template, $data = array()) {
    if (isset($this->template))
      return $this->template->render($template, $data);
    arsort($this->templateDirs);
    $this->data->flash = $this->request->session->flash;
    $this->template = new Template($this);
    $this->template->ignoreExtend(true);
    $result = $this->template->render($template, $data);
    $this->template = null;
    return $result;
  }
  
}
