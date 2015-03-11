<?php
// Jivoo
// Copyright (c) 2015 Niels Sonnich Poulsen (http://nielssp.dk)
// Licensed under the MIT license.
// See the LICENSE file or http://opensource.org/licenses/MIT for more information.
namespace Jivoo\Console;

use Jivoo\Snippets\Snippet;
use Jivoo\Models\Form;
use Jivoo\Models\DataType;
use Jivoo\Core\Json;

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
    $this->configForm->addField('modules', DataType::object(), tr('Modules'));

    $this->view->data->availableModules = $this->getModules();
    $this->view->data->configForm = $this->configForm;
    $this->view->data->title = tr('Configure application');
  }

  /**
   * {@inheritdoc}
   */
  public function get() {
    $this->configForm->name = $this->app->name;
    $this->configForm->version = $this->app->version;
    $this->configForm->modules = array(
      'Controllers', 'Snippets', 'Databases', 'Migrations', 'ActiveModels',
      'Console', 'Generators'
    );
    return $this->render();
  }

  /**
   * {@inheritdoc}
   */
  public function post($data) {
    $this->configForm->addData($data);
    $appConfig = array(
      'name' => $this->configForm->name,
      'version' => $this->configForm->version,
      'modules' => array_merge(
        array('Assets', 'Helpers', 'Models', 'Routing', 'View'),
        array_values($this->configForm->modules)
      )
    );
    mkdir($this->p('app', ''));
    mkdir($this->p('app', 'config'));
    mkdir($this->p('app', 'config/environments'));
    $this->installFile('Core', 'config/environments/development.php');
    $this->installFile('Core', 'config/environments/production.php');
    mkdir($this->p('user', ''));
    mkdir($this->p('log', ''));
    $file = fopen($this->p('app', 'app.json'), 'w');
    if ($file) {
      fwrite($file, Json::prettyPrint($appConfig));
      fclose($file);
      return $this->redirect(null);
    }
    return $this->render();
  }
  
  /**
   * Install a file from a modules default-directory into the app-directory. 
   * @param string $module Module name.
   * @param string $file Relative file path.
   */
  private function installFile($module, $file) {
    copy(
      \Jivoo\PATH . '/Jivoo/' . $module . '/default/' . $file,
      $this->p('app', $file)
    );
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