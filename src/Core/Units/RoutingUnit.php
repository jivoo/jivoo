<?php
// Jivoo
// Copyright (c) 2015 Niels Sonnich Poulsen (http://nielssp.dk)
// Licensed under the MIT license.
// See the LICENSE file or http://opensource.org/licenses/MIT for more information.
namespace Jivoo\Core\Units;

use Jivoo\Core\UnitBase;
use Jivoo\Core\App;
use Jivoo\Core\Store\Document;
use Jivoo\Controllers\ActionDispatcher;
use Jivoo\Snippets\SnippetDispatcher;
use Jivoo\Routing\Routing;
use Jivoo\Assets\Assets;
use Jivoo\View\View;

/**
 * Initializes the routing module.
 */
class RoutingUnit extends UnitBase {
  /**
   * {@inheritdoc}
   */
  protected $requires = array('Request', 'AppLogic');
  
  /**
   * {@inheritdoc}
   */
  public function run(App $app, Document $config) {
    $app->m->Routing = new Routing($app, false);
    
    $this->m->Routing->fixPath();

    $this->logger->debug(
      'Request for {path} from {ip}',
      array(
        'path' => '/' . implode('/', $app->m->request->path),
        'ip' => $app->m->request->ip
      )
    );
    
    $app->m->Routing->dispatchers->add(
      new ActionDispatcher($app)
    );

    $app->m->Routing->dispatchers->add(
      new SnippetDispatcher($app)
    );

    $app->on('ready', array($app->m->Routing, 'findRoute'));
    
    $app->m->Assets = new Assets($app);
    $app->m->Assets->runInit();
    
    $app->m->View = new View($app);
    $app->m->View->runInit();
    $app->m->addProperty('view', $app->m->View);
  }
}