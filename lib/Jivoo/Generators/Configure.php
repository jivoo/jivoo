<?php
// Jivoo
// Copyright (c) 2015 Niels Sonnich Poulsen (http://nielssp.dk)
// Licensed under the MIT license.
// See the LICENSE file or http://opensource.org/licenses/MIT for more information.
namespace Jivoo\Generators;

use Jivoo\Snippets\Snippet;

/**
 * Configure application.
 */
class Configure extends Snippet {
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
  
  /**
   * App config generation.
   * @return ViewResponse Response.
   */
  public function configure() {
    $this->title = tr('Configure application');
    $this->configForm = new Form('App');
    $this->configForm->addString('name', tr('Application name'));
    $this->configForm->addString('version', tr('Version'));
    $this->availableModules = $this->getModules();
    if ($this->request->hasValidData('App')) {
  
    }
    else {
      $this->configForm->name = $this->app->name;
      $this->configForm->version = $this->app->version;
    }
    return $this->render();
  }
}