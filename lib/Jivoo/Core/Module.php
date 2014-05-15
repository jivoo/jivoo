<?php
abstract class Module implements IEventSubject {
  // supports events -[and behaviours]
  // no more filemeta on every load?

  protected $modules = array();

  protected $app = null;
  protected $m;

  /**
   * @var AppConfig Module configuration
   */
  protected $config = null;

  /**
   * @var Request|null The Request object if available
   */
  protected $request = null;

  /**
   * @var SessionStorage|null Session storage object if available
   */
  protected $session = null;

  /**
   * @var View|null Current view if available
   */
  protected $view = null;
  
  /**
   * @var string[] List of event names fired by this module
   */
  protected $events = array();

  private $e;

  public function __construct(App $app) {
    $this->app = $app;
    $this->config = $app->config;
    $this->m = $app->getModules($this->modules);
    if (isset($this->m->Routing)) {
      $this->request = $this->m->Routing->request;
      $this->session = $this->request->session;
    }
    if (isset($this->m->Templates))
      $this->view = $this->m->Templates->view;

    $this->e = new EventManager($this, $this->app->eventManager);
  }

  /**
   * Get the absolute path of a file.
   * @param string $key Location-identifier
   * @param string $path File
   * @return string Absolute path
   */
  public function p($key, $path) {
    return $this->app->p($key, $path);
  }

  /**
   * Get the absolute path of a file relative to the public directory
   * @param string $path File
   * @return string Path
   */
  public function w($path = '') {
    return $this->app->w($path);
  }

  public function attachEventHandler($name, $callback) {
    $this->e->attachHandler($name, $callback);
  }

  public function attachEventListener(IEventListener $listener) {
    $this->e->attachListener($listener);
  }
  
  public function detachEventHandler($name, $callback) {
    $this->e->detachHandler($name, $callback);
  }
  
  public function detachEventListener(IEventListener $listener) {
    $this->e->detachListener($listener);
  }
  
  public function getEvents() {
    return $this->events;
  }
  
  public function hasEvent($name) {
    return in_array($name, $this->events);
  }

  public function triggerEvent($name, Event $event = null) {
    $this->e->trigger($name, $event);
  }
}