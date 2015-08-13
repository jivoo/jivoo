<?php
// Jivoo
// Copyright (c) 2015 Niels Sonnich Poulsen (http://nielssp.dk)
// Licensed under the MIT license.
// See the LICENSE file or http://opensource.org/licenses/MIT for more information.
namespace Jivoo\Console;

use Jivoo\Core\LoadableModule;
use Jivoo\Core\Json;
use Jivoo\Routing\RenderEvent;
use Jivoo\Core\Event;
use Jivoo\Core\ShowExceptionEvent;
use Jivoo\Core\Log\Logger;

/**
 * Developer console module.
 */
class Console extends LoadableModule {
  /**
   * {@inheritdoc}
   */
  protected $modules = array('Snippets', 'Routing', 'View', 'Assets', 'Extensions');

  /**
   * {@inheritdoc}
   */
  protected static $loadBefore = array('Setup');
  
  /**
   * {@inheritdoc}
   */
  protected $events = array('beforeOutputVariables');
  
  /**
   * @var mixed[] Associative array of variables and values.
   */
  private $variables = array();
  
  /**
   * @var mixed[] Associative array of tool ids and settings.
   */
  private $tools = array();
  
  /**
   * @var string Devbar HTML if enabled.
   */
  private $devbar = null;
  
  /**
   * @var string Devbar resource imports.
   */
  private $devbarResources = null;
  
  /**
   * {@inheritdoc}
   */
  protected function init() {
  }
  
  /**
   * {@inheritdoc}
   */
  public function afterLoad() {
    if ($this->app->noManifest) {
      if (!is_dir($this->p('user', ''))) {
        if (!mkdir($this->p('user', '')))
          throw new \Exception(tr('Could not create user directory: %1', $this->p('user', '')));
      }
      $this->m->Setup->trigger('Jivoo\Console\ManifestInstaller');
      $this->m->Routing->routes->root('snippet:Jivoo\Console\Index');
      $this->m->Routing->routes->auto('snippet:Jivoo\Console\Index');
      $this->m->Routing->routes->auto('snippet:Jivoo\Console\Configure');
      $this->m->Themes->load('flatmin');
    }
    if ($this->config->get('enable', false) === true) {

      $this->m->Extensions->import('jquery');
      $this->m->Extensions->import('jqueryui');
      $this->m->Extensions->import('js-cookie');
      
      $this->view->resources->openFrame();
      $this->devbar = $this->view->renderOnly('jivoo/console/devbar.html');
      $this->devbarResources = $this->view->resources->resourceBlock(null, false); 
      $this->view->resources->closeFrame();
      
      $this->m->Routing->attachEventHandler('afterRender', array($this, 'injectCode'));
      $this->app->attachEventHandler('beforeShowException', array($this, 'injectCode'));
      
      $this->m->Routing->routes->auto('snippet:Jivoo\Console\SystemInfo');
      $this->m->Routing->routes->auto('snippet:Jivoo\Console\Generators');
      $this->m->Routing->routes->auto('snippet:Jivoo\Console\ControlPanel');
      $this->m->Routing->routes->auto('snippet:Jivoo\Console\I18nInfo');

      $this->m->Routing->routes->auto('snippet:Jivoo\Console\Generators\SchemaGenerator');
      
      $this->addTool('system', tr('System'), 'snippet:Jivoo\Console\SystemInfo', true);
      
      $this->addTool('control-panel', tr('Control panel'), 'snippet:Jivoo\Console\ControlPanel', false);
      $this->addTool('i18n', tr('I18n'), 'snippet:Jivoo\Console\I18nInfo', false);
      $this->addTool('release', tr('Release'), 'snippet:Jivoo\Console\SystemInfo', false);
    }
  }
  
  /**
   * Event handler for {@see RenderEvent} and {@see ShowExceptionEvent}. Inserts
   * the development bar code into the response if the response type is
   * 'text/html' and the body contains '</body'.
   * @param RenderEvent|ShowExceptionEvent $event The event object.
   */
  public function injectCode(Event $event) {
    if (!isset($this->devbar))
      return;
    if ($event instanceof RenderEvent) {
      if ($event->response->type !== 'text/html')
        return;
      $extraIncludes = '';
    }
    else {
      assume($event instanceof ShowExceptionEvent);
      $extraIncludes = $this->devbarResources;
    }
    $body = $event->body;
    $pos = strripos($body, '</body');
    if ($pos === false)
      return;
    
    // TODO: maybe app logger should not be replacable?
    if ($this->logger instanceof Logger)
      $this->setVariable('jivooLog', $this->logger->getLog());
    else
      $this->setVariable('jivooLog', array());
    $this->setVariable('jivooRequest', $this->request->toArray());
    $this->setVariable('jivooSession', $this->request->session->toArray());
    $this->setVariable('jivooCookies', $this->request->cookies->toArray());
    $extraVars = '<script type="text/javascript">'
      . $this->outputVariables()
      . $this->outputTools()
      . '</script>' . PHP_EOL;
    $event->body = substr_replace($body, $extraIncludes . $this->devbar . $extraVars, $pos, 0);
    $event->overrideBody = true;
  }
  
  /**
   * Output variables to JavaScript.
   * @return string JavaScript variable assignments.
   */
  public function outputVariables() {
    $this->triggerEvent('beforeOutputVariables');
    $output = '';
    foreach ($this->variables as $variable => $value) {
      $output .= 'var ' . $variable . ' = ' . Json::encode($value) . ';';
    }
    return $output;
  }
  
  /**
   * Add a variable to the global JavaScript namespace. 
   * @param string $variable Variable name.
   * @param mixed $value Value, will be JSON encoded.
   */
  public function setVariable($variable, $value) {
    $this->variables[$variable] = $value;
  }
  
  /**
   * Get value of a variable previously set using {@see setVariable}.
   * @param string $variable Variable name.
   * @return mixed Value of variable.
   */
  public function getVariable($variable) {
    if (isset($this->variables[$variable]))
      return $this->variables[$variable];
    return null;
  }
  
  /**
   * Add an Ajax-based tool to the developer toolbar.
   * @param string $id A unique tool id.
   * @param string $name Name of tool.
   * @param array|ILinkable|string|null $route A route, see {@see Routing}.
   * @param bool $ajax Whether to use Ajax. If false, then a simple
   * link is created instead.
   * @param bool $ajaxOnly Whether to only allow Ajax (e.g. don't allow middle
   * clicking).
   */
  public function addTool($id, $name, $route, $ajax = true, $ajaxOnly = false) {
    $this->tools[$id] = array(
      'name' => $name,
      'route' => $route,
      'ajax' => $ajax,
      'ajaxOnly' => $ajaxOnly
    );
  }
  
  /**
   * Output tool creation JavaScript.
   * @return string JavaScript.
   */
  public function outputTools() {
    $output = 'if (typeof JIVOO !== "object") {';
    $output .= 'console.error("Jivoo module not found!");';
    $output .= '} else if (typeof JIVOO.devbar !== "object") {';
    $output .= 'console.error("Jivoo Devbar module not found!");';
    $output .= '} else {';
    foreach ($this->tools as $id => $tool) {
      if ($tool['ajax'])
        $output .= 'JIVOO.devbar.addAjaxTool(';
      else
        $output .= 'JIVOO.devbar.addLinkTool(';
      $output .= Json::encode($id) . ', ';
      $output .= Json::encode($tool['name']) . ', ';
      $link = $this->m->Routing->getLink($tool['route']);
      $output .= Json::encode($link);
      if ($tool['ajax'] and $tool['ajaxOnly'])
        $output .= ', true';
      $output .= ');';
    }
    $output .= '}';
    return $output;
  }
}
