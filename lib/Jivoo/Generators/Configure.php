<?php
// Jivoo
// Copyright (c) 2015 Niels Sonnich Poulsen (http://nielssp.dk)
// Licensed under the MIT license.
// See the LICENSE file or http://opensource.org/licenses/MIT for more information.
namespace Jivoo\Generators;

use Jivoo\Snippets\Snippet;
use Jivoo\Models\Form;

/**
 * Configure application.
 */
class Configure extends Snippet {
  /**
   * @var Form Configuration form.
   */
  private $configForm;

  /**
   * {@inheritdoc}
   */
  protected $helpers = array('Form');
  
  /**
   * {@inheritdoc}
   */
  public function before() {
    parent::before();
    $this->configForm = new Form('Configure');
    $this->configForm->addString('name', tr('Application name'));
    $this->configForm->addString('version', tr('Version'));

    $this->view->data->availableModules = $this->getModules();
    $this->view->data->configForm = $this->configForm;
  }

  /**
   * {@inheritdoc}
   */
  public function get() {
    $this->configForm->name = $this->app->name;
    $this->configForm->version = $this->app->version;
    return $this->render();
  }

  /**
   * {@inheritdoc}
   */
  public function post($data) {
    $this->configForm->addData($data);
    return $this->render();
  }

  /**
   * Get list of Jivoo modules.
   * @return string Module names.
   */
  private function getModules() {
    $files = scandir(\Jivoo\PATH . '/Jivoo');
    $modules = array();
    if ($files !== false) {
      foreach ($files as $file) {
        if ($file[0] == '.')
          continue;
        if (file_exists(\Jivoo\PATH . '/Jivoo/' . $file . '/' . $file . '.php')) {
          $modules[] = $file;
        }
      }
    }
    return $modules;
  }
}