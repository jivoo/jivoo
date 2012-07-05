<?php
// Module
// Name           : Pages
// Version        : 0.2.0
// Description    : The PeanutCMS content page system
// Author         : PeanutCMS
// Dependencies   : errors configuration database routes templates http
//                  users backend

/*
 * Static pages
 *
 * @package PeanutCMS
 */

/**
 * Pages class
 */
class Pages extends ModuleBase {

  private $controller;
  
  protected function init() {
    $newInstall = FALSE;

    require_once(p(MODELS . 'Page.php'));

    if (!$this->m->Database->tableExists('pages')) {
      $this->m->Database->createQuery('pages')
        ->addInt('id', TRUE, TRUE)
        ->setPrimaryKey('id')
        ->addVarchar('name', 255)
        ->addVarchar('title', 255)
        ->addText('content')
        ->addInt('date', TRUE)
        ->addVarchar('state', 10)
        ->addIndex(TRUE, 'name')
        ->addIndex(FALSE, 'date')
        ->execute();
      $newInstall = TRUE;
    }

    ActiveRecord::addModel('Page', 'pages');

    if ($newInstall) {
      $page = Page::create();
      $page->title = 'About';
      $page->name = 'about';
      $page->content = '<p>';
      $page->content .= tr('Welcome to PeanutCMS. This is a static page. You can use it to display important information.');
      $page->content .= '</p>';
      $page->date = time();
      $page->state = 'published';
      $page->save();
    }
    
    $this->controller = new PagesController($this->m->Templates, $this->m->Routes);

    $this->detectFancyPermalinks();
    $this->m->Routes->addPath('Pages', 'view', array($this, 'getFancyPath'));

    $this->m->Backend->addCategory('content', tr('Content'), 2);
    $this->m->Backend->addPage('content', 'new-page', tr('New Page'), array($this, 'addPageController'), 2);
    $this->m->Backend->addPage('content', 'manage-pages', tr('Manage Pages'), array($this, 'addPageController'), 4);
  }

  private function detectFancyPermalinks() {
    $path = $this->m->Http->getRequest()->path;
    if (!is_array($path)) {
      return;
    }
    $name = implode('/', $path);
    $page = Page::first(
      SelectQuery::create()
        ->where('name = ?')
        ->addVar($name)
    );
    if ($page === FALSE) {
      return;
    }
    $page->addToCache();
    $this->controller->setRoute('view', 6, array($page->id));
  }
  
  public function getFancyPath($parameters) {
    if (is_object($parameters) AND is_a($parameters, 'Page')) {
      $record = $parameters;
    }
    else {
      $record = Page::find($parameters[0]);
    }
    return explode('/', $record->name);
  }

  public function addPageController($path = array(), $parameters = array(), $contentType = 'html') {
    $templateData = array();
    $templateData['title'] = tr('New Page');
    $this->m->Templates->renderTemplate('backend/edit-post.html', $templateData);
  }
}
