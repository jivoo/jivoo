<?php
// Jivoo
// Copyright (c) 2015 Niels Sonnich Poulsen (http://nielssp.dk)
// Licensed under the MIT license.
// See the LICENSE file or http://opensource.org/licenses/MIT for more information.
namespace Jivoo\Core\Units;

use Jivoo\Core\UnitBase;
use Jivoo\Core\App;
use Jivoo\Core\Store\Document;
use Jivoo\Core\Cli\Shell;

/**
 * Initializes the shell.
 */
class ShellUnit extends UnitBase {
  /**
   * {@inheritdoc}
   */
  public function run(App $app, Document $config) {
    $app->m->Shell = new Shell($app);
    $app->on('ready', function() use($app) {
      $app->m->Shell->parseArgument();
      $app->m->Shell->run();
    });
  }
}