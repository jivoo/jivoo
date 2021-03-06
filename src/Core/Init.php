<?php
// Jivoo
// Copyright (c) 2015 Niels Sonnich Poulsen (http://nielssp.dk)
// Licensed under the MIT license.
// See the LICENSE file or http://opensource.org/licenses/MIT for more information.
namespace Jivoo\Core;

use Jivoo\Console\Shell;
use Jivoo\Core\I18n\I18n;
use Jivoo\Core\Cache\StoreCache;
use Psr\Log\LogLevel;
use Jivoo\Core\Store\SerializedStore;
use Jivoo\Core\Cache\StorePool;
use Jivoo\Core\Store\PhpSessionStore;
use Jivoo\Core\Store\Session;
use Jivoo\Vendor\ComposerPackageReader;

/**
 * The default application initialization class. Extend this class and override
 * the {@see boot} method to customize the initialization process. The defeault
 * implementation supports the environments 'production', 'development', and
 * 'cli'.
 */
class Init extends Module {
  /**
   * @var string[] List of valid environments used by {@see init} to select a
   * method.
   */
  protected $environments = array('production', 'development', 'cli');
  
  /**
   * Construct boot object.
   * @param App $app Application.
   */
  public final function __construct(App $app) {
    parent::__construct($app);
  }
  
  /**
   * Runs application initialization code.
   * @param string $environment Environment, must be defined as a method and
   * exist in {@see $environments}.
   * @throws InvalidEnvironmentException If the environment is undefined.
   */
  public function init($environment) {    
    if (!in_array($environment, $this->environments))
      throw new InvalidEnvironmentException(tr('Undefined environment: %1', $environment));

    if (isset($this->app->manifest['defaultConfig']))
      $this->config->defaults = $this->app->manifest['defaultConfig'];
    
    $this->m->units = new UnitLoader($this->app);

    $this->$environment();
    
    $this->m->units->runAll();
  }
  
  /**
   * Run applciation in production mode.
   */
  protected function production() {
    if (isset($this->app->manifest['init']))
      $this->m->units->enable($this->app->manifest['init']);
    $this->m->units->enable(array(
      'Cache', 'I18n', 'State', 'Vendor', 'Extensions', 'AppLogic',
      'Request', 'Routing', 'Session', 'AppRoutes'
    ));
  }

  /**
   * Run applciation in development mode.
   */
  protected function development() {
    $this->production();
    $this->m->units->enable(array('Setup', 'Console'));
  }

  /**
   * Run applciation in CLI mode.
   */
  protected function cli() {
    $this->development();
    $this->m->units->disable('Request', true);
    $this->m->units->enable('Shell');
  }
}