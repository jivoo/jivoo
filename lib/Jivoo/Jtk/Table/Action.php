<?php
// Jivoo
// Copyright (c) 2015 Niels Sonnich Poulsen (http://nielssp.dk)
// Licensed under the MIT license.
// See the LICENSE file or http://opensource.org/licenses/MIT for more information.
namespace Jivoo\Jtk\Table;

use Jivoo\Models\IBasicRecord;
use Jivoo\Jtk\JtkObject;

/**
 * Table row action.
 * @property string $label Action label.
 * @property string|array|ILinkable|null $route A route, see {@see Routing}.
 * @property string $icon Icon path or name.
 * @property array $data Optional data.
 * @property string $method Http method, e.g. 'get' or 'post'.
 * @property string $confirmation Optional confirmation dialog text.
 */
class Action extends JtkObject {
  public function __construct($label, $route = null, $icon = null) {
    $this->label = $label;
    $this->route = $route;
    $this->icon = $icon;
    $this->data = array();
    $this->method = 'get';
  }
  
  public function getRoute(IBasicRecord $record) {
    
  }
}
