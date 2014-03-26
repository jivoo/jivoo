<?php
// Module
// Name           : Widgets
// Description    : The Jivoo widget system
// Author         : apakoh.dk
// Dependencies   : Jivoo/Database Jivoo/Routing Jivoo/Templates Jivoo/Controllers
//                  Jivoo/Authentication Jivoo/Editors
//                  Jivoo/Models Jivoo/Helpers

/**
 * Widget system module
 * @package Jivoo\Widgets
 */
class Widgets extends ModuleBase {
  /**
   * @var array Associative array of widget names and objects
   */
  private $loaded = array();

  /**
   * @var array Associative array of widget names and objects
   */
  private $available = array();
  
  /**
   * @var array Associative array of widget areas and widget lists
   */
  private $areas = array();
  
  protected function init() {
    if (!isset($this->config['areas'])) {
      $this->config['areas'] = array(
        'sidebar' => array(
          array(
            'name' => 'TextWidget',
            'title' => tr('Welcome to ' . $this->app->name),
            'config' => array('text' => tr('Welcome to %1. This is a widget for displaying basic information in the sidebar.', $this->app->name))
          ),
//           array(
//             'name' => 'RecentPostsWidget',
//             'title' => tr('Recent posts'),
//             'config' => array()
//           ),
        )
      );
    }
    
    $this->register(new TextWidget(
      $this->m->Templates,
      $this->m->Routing,
      $this->p('templates/text-widget.html.php')
    ));
    
    $this->m->Routing->onRendering(array($this, 'renderWidgets'));
    
//     $this->m->Backend['appearance']->setup(tr('Appearance'), 4)
//       ->item(tr('Widgets'), null, 4);
  }
  
  /**
   * Register a widget
   * @param WidgetBase $widget Widget
   */
  public function register(WidgetBase $widget) {
    $this->available[get_class($widget)] = $widget;
  }
  
  /**
   * Call main-function of all configured widgets
   */
  public function renderWidgets() {
    $areas = $this->config['areas']->getArray();
    foreach ($areas as $area => $widgets) {
      if (!isset($this->areas[$area])) {
        $this->areas[$area] = array();
      }
      foreach ($widgets as $widget) {
        $name = $widget['name'];
        $config = $widget['config'];
        if (isset($this->loaded[$name])) {
          $object = $this->loaded[$name];
        }
        else if (isset($this->available[$name])) {
          $object = $this->available[$name];
          $this->loaded[$name] = $object;
          $this->m->Helpers->addHelpers($object);
          $this->m->Models->addModels($object);
        }
        else {
          // Widget not available.. Remove and inform user
          continue;
        }
        if (isset($widget['title'])) {
          $title = $widget['title'];
        }
        else {
          $title = $object->getDefaultTitle();
        }
        if ($object->isDefaultTemplate()) {
          // Find alternate widget template:
          $templateName = Utilities::camelCaseToDashes($name);
          $template = $this->view->findTemplate('widgets/' . $templateName . '.html');
          if ($template !== false) {
            $object->setTemplate($template);
          }
        }
        $html = $object->main($config);
        if ($html === false) {
          // Widget incorrectly configured.. Remove and inform user
          continue;
        }
        $this->areas[$area][] = array(
          'title' => $title,
          'content' => $html
        );
      }
    }
  }
  
  /**
   * Get widgets for a widget area
   * @param string $area Name of widget area 
   * @return array[] List of widgets of the following array format:
   * 
   * <code>
   * array(
   *   'title' => ..., // Widget title as string, empty for no title
   *   'content' => ..., // Widget content as string
   * )
   * </code>
   */
  public function get($area) {
    if (!isset($this->areas[$area])) {
      return array();
    }
    return $this->areas[$area];
  }
}