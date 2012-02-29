<?php
/*
 * Class for working with blog posts
 *
 * @package PeanutCMS
 * @version 0.1.2 [19-02-2012]
 */

/**
 * Posts class
 */
class Posts extends BaseObject {

  private $post;

  private $postList;

  private $commentList;

  private $commentingErrors;

  private $commentingInputs;

  /**
   * PHP5-style constructor
   */
  function __construct() {
    global $PEANUT;
    // Requires flatfiles
    if (!isset($PEANUT['flatfiles']))
      return;

    // Include models
    include(PATH . INC . 'models/post.class.php');

    //Define templates
    $PEANUT['templates']->defineTemplate('list-posts', array($this, 'getPath'), array($this, 'getTitle'));
    $PEANUT['templates']->defineTemplate('post', array($this, 'getPath'), array($this, 'getTitle'));


    // Create tables
    if (!$PEANUT['flatfiles']->tableExists('posts')) {
      $PEANUT['flatfiles']->createTable('posts');
    }
    if (!$PEANUT['flatfiles']->tableExists('tags')) {
      $PEANUT['flatfiles']->createTable('tags');
    }
    if (!$PEANUT['flatfiles']->tableExists('comments')) {
      $PEANUT['flatfiles']->createTable('comments');
    }

    // Create indexes
    if (!$PEANUT['flatfiles']->indexExists('posts', 'name'))
      $PEANUT['flatfiles']->buildIndex('posts', 'name');

    if (!$PEANUT['flatfiles']->indexExists('posts', 'date'))
      $PEANUT['flatfiles']->buildIndex('posts', 'date');

    if (!$PEANUT['flatfiles']->indexExists('tags', 'name'))
      $PEANUT['flatfiles']->buildIndex('tags', 'name');

    if (!$PEANUT['flatfiles']->indexExists('comments', 'post'))
      $PEANUT['flatfiles']->buildIndex('comments', 'post');
    if (!$PEANUT['flatfiles']->indexExists('comments', 'date'))
      $PEANUT['flatfiles']->buildIndex('comments', 'date');

    if (!$PEANUT['flatfiles']->relIndexExists('tags', 'posts'))
      $PEANUT['flatfiles']->createRelIndex('tags', 'posts');



    // Set default settings
    if (!$PEANUT['configuration']->exists('fancyPostPermalinks'))
      $PEANUT['configuration']->set('fancyPostPermalinks', 'on');
    if (!$PEANUT['configuration']->exists('postPermalink'))
      $PEANUT['configuration']->set('postPermalink', array('%year%', '%month%', '%name%'));
    if (!$PEANUT['configuration']->exists('commentSorting'))
      $PEANUT['configuration']->set('commentSorting', 'desc');
    if (!$PEANUT['configuration']->exists('commentChildSorting'))
      $PEANUT['configuration']->set('commentChildSorting', 'asc');
    if (!$PEANUT['configuration']->exists('commentDisplay'))
      $PEANUT['configuration']->set('commentDisplay', 'thread');
    if (!$PEANUT['configuration']->exists('commentLevelLimit'))
      $PEANUT['configuration']->set('commentLevelLimit', '2');
    if (!$PEANUT['configuration']->exists('commentingDefault'))
      $PEANUT['configuration']->set('commentingDefault', 'on');
    if (!$PEANUT['configuration']->exists('anonymousCommenting'))
      $PEANUT['configuration']->set('anonymousCommenting', 'off');
    if (!$PEANUT['configuration']->exists('commentApproval'))
      $PEANUT['configuration']->set('commentApproval', 'off');

    if ($PEANUT['actions']->has('rebuild')) {
      $PEANUT['flatfiles']->buildIndex('posts', 'name');
      $PEANUT['flatfiles']->buildIndex('posts', 'date');
      $PEANUT['flatfiles']->buildIndex('comments', 'date');
      $PEANUT['flatfiles']->buildIndex('comments', 'post');
      $PEANUT['flatfiles']->buildIndex('tags', 'name');
    }

    if ($PEANUT['configuration']->get('fancyPostPermalinks') == 'on') {
      // Detect fancy post permalinks
      $this->detectFancyPermalinks();
    }
    else {
      $PEANUT['routes']->addRoute('posts/*', array($this, 'postController'));
    }
    $PEANUT['routes']->addRoute('posts', array($this, 'postListController'));

    $PEANUT['hooks']->attach('finalTemplate', array($this, 'isFinal'));
  }

  function detectFancyPermalinks() {
    global $PEANUT;
    $path = $PEANUT['http']->path;
    $permalink = $PEANUT['configuration']->get('postPermalink');
    if (is_array($path)) {
      foreach ($permalink as $key => $dir) {
        if (isset($path[$key])) {
          $pos = strpos($dir, '%name%');
          $len = strlen($dir);
          if ($pos !== false) {
            $dif = $len - ($pos + 6);
//            echo "Pos = $pos, Len = $len, Dif = $dif, Name = ";
            if ($dif != 0) {
              $name = substr($path[$key], $pos, -$dif);
            }
            else {
              $name = substr($path[$key], $pos);
            }
            if (!empty($name)) {
              try {
                $post = Post::getByName($name);
                $perma = $post->path;
                if ($perma !== false) {
                  if ($perma == $path) {
                    $post->addToCache();
                    $this->post = $post->id;
                    $PEANUT['routes']->setRoute(array($this, 'postController'), 6);
                    return;
                  }
                }
              }
              catch (PostNotFoundException $e) {
                
              }
            }
          }
          $pos = strpos($dir, '%id%');
          $len = strlen($dir);
          if ($pos !== false) {
            $dif = $len - ($pos + 4);
//            echo "Pos = $pos, Len = $len, Dif = $dif, Name = ";
            if ($dif != 0) {
              $postid = substr($path[$key], $pos, -$dif);
            }
            else {
              $postid = substr($path[$key], $pos);
            }
            $post = Post::getById($postid);
            $perma = $post->path;
            if ($perma !== false) {
              if ($perma == $path) {
                $post->addToCache();
                $this->post = $post->id;
                $PEANUT['routes']->setRoute(array($this, 'postController'), 6);
                return;
              }
            }
          }
        }
      }
      foreach ($path as $name) {
        if (!empty($name)) {
          try {
            $post = Post::getByName($name);
            $post->addToCache();
            $this->post = $post->id;
            $PEANUT['routes']->setRoute(array($this, 'postController'), 3);
          }
          catch (PostNotFoundException $e) {
            
          }
        }
      }
    }
  }

  function isFinal() {
    global $PEANUT;
    /**
     * @todo Rewrite the following codeblock
     */
    // Create comment
    if (isset($this->post) AND (!isset($this->post['commenting']) OR $this->post['commenting'] !== 'off')) {
      if ($PEANUT['actions']->has('comment', 'post')) {
        $this->commentingErrors = array();
        $name = $_POST['name'];
        $email = $_POST['email'];
        $website = $_POST['website'];
        $comment = $_POST['comment'];
        $parent = $_POST['parent'];
        if (isset($parent) AND $PEANUT['flatfiles']->getRow('comments', $parent) !== false)
          $PEANUT['http']->params['reply-to'] = $parent;
        else
          $parent = '';

        $this->commentingInputs = compact('name', 'email', 'website', 'comment');
        foreach ($this->commentingInputs as $key => $value) {
          $this->commentingInputs[$key] = htmlentities($value, ENT_COMPAT, 'UTF-8');
        }
        if (empty($name) AND $PEANUT['configuration']->get('anonymousCommenting') != on) {
          $this->commentingErrors['name'] = tr('The name cannot be empty');
        }
        else if (strlen($name) > 100) {
          $this->commentingErrors['name'] = tr('The name is too long');
        }
        if (empty($email) AND $PEANUT['configuration']->get('anonymousCommenting') != on) {
          $this->commentingErrors['email'] = tr('The email cannot be empty');
        }
        else if (preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $email) !== 1 AND $PEANUT['configuration']->get('anonymousCommenting') != on) {
          $this->commentingErrors['email'] = tr('The email is not valid');
        }
        else if (strlen($email) > 200) {
          $this->commentingErrors['email'] = tr('The email is too long');
        }
        if (strlen($website) > 200) {
          $this->commentingErrors['website'] = tr('The website is too long');
        }
        if (empty($comment)) {
          $this->commentingErrors['comment'] = tr('The comment cannot be empty');
        }
        else if (strlen($comment) > 1000) {
          $this->commentingErrors['comment'] = tr('The comment is too long');
        }
        if (count($this->commentingErrors) === 0) {
          if (isset($website) AND strpos('http://', $website) === false AND strpos('https://', $website) === false)
            $website = 'http://' . $website;
          $commentid = $this->createComment($this->post['id'], $name, $email, $website, $comment, $parent);
          unset($PEANUT['http']->params['reply-to']);
          $PEANUT['http']->refreshPath(null, 'comment-' . $commentid);
        }
      }
    }

  }

  function deletePost() {
    global $PEANUT;
    $postId = $_GET['p'];
    if (!$PEANUT['flatfiles']->removeRow('posts', $postId)) {
      $PEANUT['errors']->notification('error', tr('The post could not be deleted'), false);
      return;
    }
    $indexPost = $PEANUT['flatfiles']->getIndex('comments', 'post');
    foreach ($indexPost as $commentId => $commentPostId) {
      if ($commentPostId != $postId)
        continue;
      $PEANUT['flatfiles']->removeRow('comments', $commentId);
    }
    $tagIds = $PEANUT['flatfiles']->getRelations('tags', 'posts', null, $_GET['p']);
    foreach ($tagIds as $tagId) {
      $PEANUT['flatfiles']->removeRelation('tags', 'posts', $tagId, $_GET['p']);
    }
    $PEANUT['errors']->notification('notice', tr('The post has been deleted'), false);
    $PEANUT['http']->redirectPath(null, array('backend' => 'posts'), false);
  }

  function editPost() {
    global $PEANUT;
    $error = '';


    if (!isset($_GET['p']) OR ($post = $PEANUT['flatfiles']->getRow('posts', $_GET['p'])) === false)
      $error = tr('The post was not found');

    $tagIds = $PEANUT['flatfiles']->getRelations('tags', 'posts', null, $_GET['p']);
    foreach ($tagIds as $tagId) {
      $PEANUT['flatfiles']->removeRelation('tags', 'posts', $tagId, $_GET['p']);
    }

    $tagInput = explode(',', $_POST['tags']);
    $tags = array();
    foreach($tagInput as $tag) {
      $tag = trim($tag);
      if (!empty($tag)) {
        $tags[] = $tag;
      }
    }

    if (empty($_POST['title']))
      $error = tr('The title should not be empty');
    else if (empty($_POST['content']))
      $error = tr('The content should not be empty');

    if ($error == '') {
      if ($_POST['status'] == 'published')
        $state = 'published';
      else
        $state = 'unpublished';

      $postArray = array(
          'name' => $post['name'],
          'title' => $_POST['title'],
          'date' => $post['date'],
          'state' => $state,
          'commenting' => $_POST['commenting'],
          'comments' => $_POST['comments'],
          'content' => $_POST['content']
      );
      $PEANUT['flatfiles']->insertRow('posts', $_GET['p'], $postArray);
      foreach ($tags as $tag) {
        $tagName = strtolower(preg_replace('/[ \-]/', '-', preg_replace('/[^(a-zA-Z0-9 \-)]/', '', $tag)));
        $tagId = $PEANUT['flatfiles']->indexFind('tags', 'name', $tagName);
        if ($PEANUT['flatfiles']->getRow('tags', $tagId) !== false) {
          $PEANUT['flatfiles']->addRelation('tags', 'posts', $tagId, $_GET['p']);
        }
        else {
          $tagId = $PEANUT['flatfiles']->incrementId('tags');
          $PEANUT['flatfiles']->insertRow('tags', $tagId, array('name' => $tagName, 'tag' => $tag));
          $PEANUT['flatfiles']->addRelation('tags', 'posts', $tagId, $_GET['p']);
        }
      }

      if ($state == 'unpublished')
        $PEANUT['errors']->notification('notice', tr('Your post has been saved'), false);
      else
        $PEANUT['errors']->notification('notice', tr('Your post has been published'), false);
      $PEANUT['http']->redirectPath(null, array('backend' => 'edit-post', 'p' => $_GET['p']), false);
    }
    else {
      $PEANUT['errors']->notification('error', $error, false);
    }
  }

  function submitPost() {
    global $PEANUT;
    $error = '';


    $tagInput = explode(',', $_POST['tags']);
    $tags = array();
    foreach($tagInput as $tag) {
      $tag = trim($tag);
      if (!empty($tag)) {
        $tags[] = $tag;
      }
    }

    $name = null;
    if (isset($_POST['name'])) {
      $name = strtolower(preg_replace('/[ \-]/', '-', preg_replace('/[^(a-zA-Z0-9 \-)]/', '', $_POST['name'])));
      if (empty($name))
        $name = null;
    }

    if (!isset($name))
      $name = strtolower(preg_replace('/[ \-]/', '-', preg_replace('/[^(a-zA-Z0-9 \-)]/', '', $_POST['title'])));

    $postid = $PEANUT['flatfiles']->indexFind('posts', 'name', $name);
    if ($postid !== false AND ($post = $PEANUT['flatfiles']->getRow('posts', $postid)) !== false)
      $error = tr('A post with that name already exists');

    if (empty($name))
      $error = tr('The name should not be empty');

    if (empty($_POST['title']))
      $error = tr('The title should not be empty');
    else if (empty($_POST['content']))
      $error = tr('The content should not be empty');

    if ($error == '') {
      if (isset($_POST['save']))
        $state = 'unpublished';
      else
        $state = 'published';
      $id = $this->createPost($_POST['title'], $_POST['content'], $state, $name, $tags);
      if ($state == 'unpublished')
        $PEANUT['errors']->notification('notice', tr('Your post has been saved'), false);
      else
        $PEANUT['errors']->notification('notice', tr('Your post has been published'), false);
      $PEANUT['http']->redirectPath(null, array('backend' => 'edit-post', 'p' => $id), false);
    }
    else {
      $PEANUT['errors']->notification('error', $error, false);
    }
  }

  function createPost($title, $content, $state = 'unpuplished', $name = null, $tags = array(), $commenting = null) {
    global $PEANUT;
    $date = time();
    $id = $PEANUT['flatfiles']->incrementId('posts');
    if ($id === false)
      return false;
    if (!isset($name)) // Remove all non-alphanumeric characters, replace whitespaces with dashes and convert to lowercase
      $name = strtolower(preg_replace('/[ \-]/', '-', preg_replace('/[^(a-zA-Z0-9 \-)]/', '', $title)));
    if ($PEANUT['configuration']->get('commentingDefault') == 'on' AND (!isset($commenting) OR $commenting == 'off')
            OR (isset($commenting) AND $commenting == 'on'))
      $commenting = 'on';
    else
      $commenting = 'off';
    $post = array(
        'name' => $name,
        'title' => $title,
        'date' => $date,
        'state' => $state,
        'commenting' => $commenting,
        'comments' => 0,
        'content' => $content
    );
    $PEANUT['flatfiles']->insertRow('posts', $id, $post);
    foreach ($tags as $tag) {
      $tagName = strtolower(preg_replace('/[ \-]/', '-', preg_replace('/[^(a-zA-Z0-9 \-)]/', '', $tag)));
      $tagId = $PEANUT['flatfiles']->indexFind('tags', 'name', $tagName);
      if ($PEANUT['flatfiles']->getRow('tags', $tagId) !== false) {
        $PEANUT['flatfiles']->addRelation('tags', 'posts', $tagId, $id);
      }
      else {
        $tagId = $PEANUT['flatfiles']->incrementId('tags');
        $PEANUT['flatfiles']->insertRow('tags', $tagId, array('name' => $tagName, 'tag' => $tag));
        $PEANUT['flatfiles']->addRelation('tags', 'posts', $tagId, $id);
      }
    }
    return $id;
  }

  function getTags($id) {
    global $PEANUT;
    $tagIds = $PEANUT['flatfiles']->getRelations('tags', 'posts', null, $id);
    $tags = array();
    foreach ($tagIds as $tagId) {
      if (($tag = $PEANUT['flatfiles']->getRow('tags', $tagId)) !== false)
        $tags[$tag['name']] = htmlentities($tag['tag'], ENT_QUOTES, 'UTF-8');
    }
    return $tags;
  }

  function updatePost($id, $column, $value) {
    global $PEANUT;
    if (($post = $PEANUT['flatfiles']->getRow('posts', $id)) === false)
      return;
    $post[$column] = $value;
    $PEANUT['flatfiles']->insertRow('posts', $id, $post);
  }

  function createComment($post, $author, $email, $website, $content, $parent = '') {
    global $PEANUT;
    $date = time();
    $id = $PEANUT['flatfiles']->incrementId('comments');
    if ($id === false)
      return false;
    $ip = $_SERVER['REMOTE_ADDR'];
    if ($PEANUT['configuration']->get('commentApproval') == 'on')
      $state = 'unapproved';
    else
      $state = 'approved';
    $comment = array(
        'post' => $post,
        'parent' => $parent,
        'author' => $author,
        'ip' => $ip,
        'email' => $email,
        'website' => $website,
        'date' => $date,
        'state' => $state,
        'content' => $content
    );
    $PEANUT['flatfiles']->insertRow('comments', (string)$id, $comment);
    $postRow = $PEANUT['flatfiles']->getRow('posts', $post);
    $postRow = array_merge($postRow, array('comments' => 1 + $postRow['comments']));
    $PEANUT['flatfiles']->insertRow('posts', $post, $postRow);
    return $id;
  }

  function getPath($template, $parameters = array()) {
    global $PEANUT;
    switch ($template) {
      case 'list-posts':
        break;
      case 'post':
        if (!empty($parameters['p'])) {
          if (($post = $PEANUT['flatfiles']->getRow('posts', $parameters['p'])) !== false) {
            $permalink = $PEANUT['configuration']->get('postPermalink');
            if (is_array($permalink)) {
              $time = $post['date'];
              $replace = array('%name%' => $post['name'],
                               '%id%' => $parameters['p'],
                               '%year%' => $PEANUT['i18n']->date('Y', $time),
                               '%month%' => $PEANUT['i18n']->date('m', $time),
                               '%day%' => $PEANUT['i18n']->date('d', $time));
              $search = array_keys($replace);
              $replace = array_values($replace);
              $path = array();
              foreach ($permalink as $dir) {
                $path[] = str_replace($search, $replace, $dir);
              }
              return $path;
            }
          }
        }
        break;
      default:
        break;

    }
  }

  function getExampleLink($placeholder, $format = null) {
    global $PEANUT;
    if (isset($format))
      $permalink = explode('/', $format);
    else
      $permalink = $PEANUT['configuration']->get('postPermalink');
    if (is_array($permalink)) {
      $replace = array('%name%' => $placeholder,
                       '%id%' => $PEANUT['flatfiles']->tableIndex['posts']['incrementation'],
                       '%year%' => $PEANUT['i18n']->date('Y'),
                       '%month%' => $PEANUT['i18n']->date('m'),
                       '%day%' => $PEANUT['i18n']->date('d'));
      $search = array_keys($replace);
      $replace = array_values($replace);
      $path = array();
      foreach ($permalink as $dir) {
        $path[] = str_replace($search, $replace, $dir);
      }
      return $PEANUT['http']->getLink($path);
    }
  }

  function getTitle($template, $parameters = array()) {
    global $PEANUT;
    switch ($template) {
      case 'list-posts':
        break;
      case 'post':
        if (!empty($parameters['p'])) {
          if (($post = $PEANUT['flatfiles']->getRow('posts', $parameters['p'])) !== false) {
            return $post['title'];
          }
        }
        break;
      default:
        break;

    }
  }

  function listPosts() {
    global $PEANUT;
    if (is_array($this->postList))
      return next($this->postList);
    $index = $PEANUT['flatfiles']->getIndex('posts', 'date');
    arsort($index);
    reset($index);
    $this->postList = array();
    foreach ($index as $id => $date) {
      $post = $PEANUT['flatfiles']->getRow('posts', $id);
      if ($post === false)
        continue;
      $this->postList[$id] = $post;
      $content = explode('<!-- pagebreak -->', $this->postList[$id]['content']);
      $this->postList[$id]['content'] = $content[0];
      $this->postList[$id]['more'] = isset($content[1]);
      $this->postList[$id]['content'] = $this->addPostActions($id) . $this->postList[$id]['content'];
      $this->postList[$id]['path'] = $this->getPath('post', array('p' => $id));
      $this->postList[$id]['link'] = $PEANUT['http']->getLink($this->postList[$id]['path']);
    }
    reset($this->postList);
    return current($this->postList);
  }

  function listComments() {
    global $PEANUT;
    if (!is_array($this->post))
      return false;
    if (is_array($this->commentList))
      return next($this->commentList);
    $indexPost = $PEANUT['flatfiles']->getIndex('comments', 'post');
    $indexDate = $PEANUT['flatfiles']->getIndex('comments', 'date');
    $sorting = $PEANUT['configuration']->get('commentSorting');
    if ($sorting == 'asc')
      asort($indexDate);
    else
      arsort($indexDate);
    reset($indexDate);
    $this->commentList = array();
    $comments = array();
    foreach ($indexDate as $id => $date) {
      if ($indexPost[$id] != $this->post['id'])
        continue;
      $comment = $PEANUT['flatfiles']->getRow('comments', $id);
      if ($comment === false)
        continue;
      $comments[$id] = $comment;
      $comments[$id]['reply'] = true;
    }
    $display = $PEANUT['configuration']->get('commentDisplay');
    if ($display == 'thread')
      $this->commentList = $this->getCommentThreads($comments);
    else
      $this->commentList = $comments;
    $comments = count($this->commentList);
    if ($comments != $this->post['comments']);
      $PEANUT['flatfiles']->updateRow('posts', $this->post['id'], array('comments' => $comments));
    reset($this->commentList);
    return current($this->commentList);
  }

  /**
   * A recursive function that returns an array of comments ordered in threads
   *
   * @param array $comments Comments array (as returned from flatfiles-table)
   * @param string $parent Parent comment
   * @param int $level Level counter
   * @return array An ordered comments array
   */
  function getCommentThreads($comments, $parent = null, $level = 0) {
    global $PEANUT;
    $dataArray = array();
    if ($level != 0 AND $PEANUT['configuration']->settings['commentSorting'] != $PEANUT['configuration']->settings['commentChildSorting'])
      uasort($comments, array($this, 'commentSorter'));
    reset($comments);
    foreach ($comments as $id => $comment) {
      if ((empty($comment['parent']) AND $parent == null) OR $comment['parent'] == $parent) {
        $dataArray[$id] = $comment;
        $dataArray[$id]['level'] = $level;
        if ($level > $PEANUT['configuration']->settings['commentLevelLimit'])
          $dataArray[$id]['reply'] = false;
        $dataArray = $dataArray + $this->getCommentThreads($comments, $id, $level + 1);
      }
    }
    return $dataArray;
  }

  function commentSorter($a, $b) {
    global $PEANUT;
    if ($a['date'] == $b['date'])
      return 0;
    if ($PEANUT['configuration']->settings['commentChildSorting'] == 'asc') {
      if ($a['date'] < $b['date'])
        return -1;
      else
        return 1;
    }
    else {
      if ($a['date'] < $b['date'])
        return 1;
      else
        return -1;
    }
  }

  private function addPostActions($id) {
    global $PEANUT;
    if ($PEANUT['user']->isLoggedIn()) {
      $buttons = '<span class="backend-buttonset">';
      $buttons .= '<a href="' . $PEANUT['http']->getLink(null, array('backend' => 'edit-post', 'p' => $id)) .
              '" class="backend-button" rev="ui-icon-pencil">' . tr('Edit') . '</a>';
      $buttons .= '<a href="' . $PEANUT['http']->getLink(null, array('backend' => 'delete-post', 'p' => $id)) .
              '" class="backend-button" rev="ui-icon-trash">' . tr('Delete') . '</a>';
      $buttons .= '</span>';
      return $buttons;
    }
    return '';
  }


  public function postListController($parameters = array(), $contentType = 'html') {
    global $PEANUT;

    $templateData = array();

    $templateData['posts'] = Post::select(
      Selector::create()
        ->where('state', 'unpublished')
        ->orderBy('date')
        ->desc()
        ->limit(5)
        ->offset(0)
    );

    $PEANUT['templates']->renderTemplate('list-posts.html', $templateData);
  }

  public function postController($parameters = array(), $contentType = 'html') {
    global $PEANUT;

    $templateData = array();

    if ($PEANUT['configuration']->get('fancyPostPermalinks') == 'on') {
      $templateData['post'] = Post::getById($this->post);
    }
    else {
      $templateData['post'] = Post::getById($PEANUT['http']->path[1]);
    }
    
    if ($templateData['post']->path != $PEANUT['http']->path) {
      $PEANUT['http']->redirectPath($templateData['post']->path);
    }
    /**
     * Just testing...
     * @todo JSON interface/whatever...
     */
    if (isset($parameters['json'])) {
      header('Content-Type: application/json;charset=utf-8');
      echo $templateData['post']->json();
    }
    else {
      $PEANUT['templates']->renderTemplate('post.html', $templateData);
    }
  }
}