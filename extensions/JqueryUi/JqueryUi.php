<?php
// Extension
// Name         : jQuery UI
// Category     : JavaScript jQuery
// Website      : http://jqueryui.com
// Version      : 1.8.17
// Dependencies : Templates Assets ext;Jquery>=1.3.2

class JqueryUi extends ExtensionBase {
  private $theme;

  protected function init() {
    if (!$this->config->exists('theme')) {
      $this->config->set('theme', 'arachis');
    }
    $this->theme = $this->config->get('theme');

    $this->view->provide(
      'jquery-ui.js',
      $this->getAsset('js/jquery-ui-1.8.17.custom.min.js'),
      array('jquery.js', 'jquery-ui.css')
    );

    $this->view->provide(
      'jquery-ui.css',
      $this->getAsset('css/' . $this->theme . '/jquery-ui-1.8.17.custom.css')
    );
  }
}
