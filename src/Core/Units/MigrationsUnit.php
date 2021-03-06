<?php
// Jivoo
// Copyright (c) 2015 Niels Sonnich Poulsen (http://nielssp.dk)
// Licensed under the MIT license.
// See the LICENSE file or http://opensource.org/licenses/MIT for more information.
namespace Jivoo\Core\Units;

use Jivoo\Core\UnitBase;
use Jivoo\Core\App;
use Jivoo\Core\Store\Document;
use Jivoo\Core\LoadableModule;
use Jivoo\Databases\DatabaseLoader;
use Jivoo\Migrations\Migrations;

/**
 * Initializes the migration system.
 */
class MigrationsUnit extends UnitBase {  
  /**
   * {@inheritdoc}
   */
  protected $requires = array('Databases');
  
  /**
   * {@inheritdoc}
   */
  public function run(App $app, Document $config) {
    $this->m->Migrations = new Migrations($app);
    $this->m->Migrations->runInit();
  }
}