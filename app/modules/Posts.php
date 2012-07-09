<?php
// Module
// Name           : Posts
// Version        : 0.3.0
// Description    : The PeanutCMS blogging system
// Author         : PeanutCMS
// Dependencies   : errors configuration database routes templates http
//                  users backend

/*
 * Class for working with blog posts
 *
 * @package PeanutCMS
 */

/**
 * Posts class
 */
class Posts extends ModuleBase {
  private $post;
  
  private $controller;

  protected function init() {

    $newInstall = FALSE;

    $postsSchema = new postsSchema();
    $tagsSchema = new tagsSchema();
    $posts_tagsSchema = new posts_tagsSchema();
    $commentsSchema = new commentsSchema();

    $newInstall = $this->m->Database->migrate($postsSchema) == 'new';
    $this->m->Database->migrate($tagsSchema);
    $this->m->Database->migrate($posts_tagsSchema);
    $this->m->Database->migrate($commentsSchema);

    $this->m->Database->posts->setSchema($postsSchema);
    $this->m->Database->tags->setSchema($tagsSchema);
    $this->m->Database->posts_tags->setSchema($posts_tagsSchema);
    $this->m->Database->comments->setSchema($commentsSchema);

    Post::connect($this->m->Database->posts);
    Tag::connect($this->m->Database->tags);
    Comment::connect($this->m->Database->comments);

    if ($newInstall) {
      $post = Post::create();
      $post->title = 'Welcome to PeanutCMS';
      $post->name = 'welcome-to-peanutcms';
      $post->content = '<p>Welcome to PeanutCMS.</p>';
      $post->date = time();
      $post->comments = 0;
      $post->state = 'published';
      $post->commenting = 'on';
      $post->save();
      $comment = Comment::create();
      $comment->author = 'PeanutCMS';
      $comment->content = 'Welcome to PeanutCMS.';
      $comment->date = time();
      $comment->setPost($post);
      $comment->save();
    }

    // Set default settings
    $this->m->Configuration->setDefault(array(
      'posts.fancyPermalinks' => 'on',
      'posts.permalink' => '%year%/%month%/%name%',
      'posts.comments.sorting' => 'desc',
      'posts.comments.childSorting' => 'asc',
      'posts.comments.display' => 'thread',
      'posts.comments.levelLimit' => '2',
      'posts.commentingDefault' => 'on',
      'posts.anonymousCommenting' => 'off',
      'posts.commentApproval' => 'off'
    ));
    
    // Create controller
    $this->controller = new PostsController($this->m->Templates, $this->m->Routes);

    $this->controller->addRoute('posts', 'index');

    $this->controller->addRoute('tags', 'tagIndex');
    $this->controller->addRoute('tags/*', 'viewTag');

    if ($this->m->Configuration->get('posts.fancyPermalinks') == 'on') {
      // Detect fancy post permalinks
      $this->detectFancyPath();
      $this->m->Routes->addPath('Posts', 'view', array($this, 'getFancyPath'));
      $this->m->Routes->addPath('Posts', 'commentIndex', array($this, 'getFancyPath'));
      $this->m->Routes->addPath('Posts', 'viewComment', array($this, 'getFancyPath'));
    }
    else {
      $this->controller->addRoute('posts/*', 'view');
      $this->controller->addRoute('posts/*/comments', 'commentIndex');
      $this->controller->addRoute('posts/*/comments/*', 'viewComment');
    }
    
    $this->m->Backend->addCategory('content', tr('Content'), 2);
    $this->m->Backend->addPage('content', 'new-post', tr('New Post'), array($this->controller, 'add'), 2);
    $this->m->Backend->addPage('content', 'manage-posts', tr('Manage Posts'), array($this, 'newPostController'), 4);
    $this->m->Backend->addPage('content', 'tags', tr('Tags'), array($this, 'newPostController'), 8);
    $this->m->Backend->addPage('content', 'categories', tr('Categories'), array($this, 'newPostController'), 8);
  }

  private function detectFancyPath() {
    $path = $this->m->Http->getRequest()->path;
    $permalink = explode('/', $this->m->Configuration->get('posts.permalink'));
    if (!is_array($path) OR !is_array($permalink)) {
      return;
    }
    if (count($path) != count($permalink)) {
      return;
    }
    $name = '';
    $id = 0;
    foreach ($permalink as $key => $dir) {
      if (empty($path[$key])) {
        return;
      }
      switch ($dir) {
        case '%year%':
          if (preg_match('/^[0-9]{4}$/', $path[$key]) !== 1) {
            return;
          }
          break;
        case '%month%':
          if (preg_match('/^[0-9]{2}$/', $path[$key]) !== 1) {
            return;
          }
          break;
        case '%day%':
          if (preg_match('/^[0-9]{2}$/', $path[$key]) !== 1) {
            return;
          }
          break;
        case '%name%':
          $name = $path[$key];
          break;
        case '%id%':
          if (preg_match('/^[0-9]+$/', $path[$key]) !== 1) {
            return;
          }
          $id = $path[$key];
          break;
        default:
          if ($dir != $path[$key]) {
            return;
          }
          break;
      }
    }
    if ($id > 0) {
      $post = Post::find($id);
      if ($post !== FALSE) {
        $post->addToCache();
        $this->controller->setRoute('view', 6, array($post->id));
        return;
      }
    }
    else if (!empty($name)) {
      $post = Post::first(
        SelectQuery::create()
          ->where('name = ?')
          ->addVar($name)
      );
      if ($post !== FALSE) {
        $post->addToCache();
        $this->controller->setRoute('view', 6, array($post->id));
        return;
      }
    }
  }

  public function getFancyPath($parameters) {
    $permalink = explode('/', $this->m->Configuration->get('posts.permalink'));
    if (is_array($permalink)) {
      if (is_object($parameters) AND is_a($parameters, 'Post')) {
        $record = $parameters;
      }
      else {
        $record = Post::find($parameters[0]);
      }
      $time = $record->date;
      $replace = array('%name%'  => $record->name,
                       '%id%'    => (isset($record->id)) ? $record->id : 0,
                       '%year%'  => tdate('Y', $time),
                       '%month%' => tdate('m', $time),
                       '%day%'   => tdate('d', $time));
      $search = array_keys($replace);
      $replace = array_values($replace);
      $path = array();
      foreach ($permalink as $dir) {
        $path[] = str_replace($search, $replace, $dir);
      }
      return $path;
    }
    return FALSE;
  }
}
