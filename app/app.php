<?php
return array(
  'path' => str_replace('\\', '/', dirname(__FILE__)),
  'name' => 'Jivoo',
  'version' => '0.15-dev-4',
  'website' => 'http://jivoo.org',
  'defaultLanguage' => 'en',
  'minPhpVersion' => '5.2.0',
  'sessionPrefix' => 'jivoo_',
  'extensions' => array('Jquery', 'JqueryHotkeys', 'JqueryUi', 'Tinymce', 'BasicWidgets', 'Html5shiv', 'Respond'),
  'import' => array(
    'Jivoo/Core',
    'Jivoo/Routing',
    'Jivoo/Assets',
    'Jivoo/Templates',
    'Jivoo/Controllers',
    'Jivoo/Setup',
    'Jivoo/Models', 
    'Jivoo/Editors',
    'Jivoo/Database',
    'Jivoo/Helpers',
    'Jivoo/AccessControl',
    'Jivoo/Administration',
    'Jivoo/Theme',
    'Jivoo/Extensions',
    'Jivoo/Widgets',
  ),
  'setup' => array(
  	'Setup::Database::selectDriver',
  	'Setup::Database::setupDriver',
    'Setup::Auth::createUser',
  ),
  'listeners' => array(
    'PageRouting',
    'PostRouting',
  ),
  'defaultConfig' => array(
    'blog' => array(
      'fancyPermalinks' => true,
      'permalink' => '%year%/%month%/%name%',
      'comments' => array(
        'sorting' => 'desc',
        'childSorting' => 'asc',
        'display' => 'thread',
        'levelLimit' => 2,
        'editor' => array(
          'name' => 'HtmlEditor'
        ),
      ),
      'commentingDefault' => true,
      'anonymousCommenting' => false,
      'commentApproval' => false,
      'editor' => array('name' => 'TinymceEditor'),
    ),
  ),
);
