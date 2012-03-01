<?php
/**
 * @brief Blog post data model
 */

class Post extends BaseModel {

  /**
   * This is neccesary for inherited addToCache() method to work properly.
   * @var array
   */
  public static $cache = array();

  private $tags = NULL;

  protected $id;
  protected $name;
  protected $title;
  protected $content;
  protected $date;
  protected $state;
  protected $comments;
  protected $commenting;

  private $updated;

  /* PROPERTIES BEGIN */

  /**
   * Array of readable property names
   * @var array
   */
  protected $_getters = array(
    'id', 'name', 'title', 'content',
    'date', 'state', 'comments', 'commenting',
  );
  /**
   * Array of writable property names
   * @var array
   */
  protected $_setters = array(
    'name', 'title', 'content', 'date',
    'state', 'commenting',
  );

  protected function _get_path() {
    global $PEANUT;
    if ($PEANUT['configuration']->get('fancyPostPermalinks') == 'on') {
      $permalink = $PEANUT['configuration']->get('postPermalink');
      if (is_array($permalink)) {
        $time = $this->date;
        $replace = array('%name%'  => $this->name,
                         '%id%'    => $this->id,
                         '%year%'  => $PEANUT['i18n']->date('Y', $time),
                         '%month%' => $PEANUT['i18n']->date('m', $time),
                         '%day%'   => $PEANUT['i18n']->date('d', $time));
        $search = array_keys($replace);
        $replace = array_values($replace);
        $path = array();
        foreach ($permalink as $dir) {
          $path[] = str_replace($search, $replace, $dir);
        }
        return $path;
      }
    }
    else {
      return array('posts', $this->id);
    }
  }

  protected function _get_link() {
    global $PEANUT;
    return $PEANUT['http']->getLink($this->path);
  }

  protected function _get_tags() {
    if (!is_array($this->tags)) {
      $this->tags = Tag::select(
        Selector::create()
          ->relation('posts', $this->id)
          ->orderBy('name')
          ->desc()
      );
    }
    return $this->tags;
  }
  /* PROPERTIES END */

  public static function create($title, $content, $state = 'unpublished',
                                $name = null, $tags = array(),
                                $commenting = null) {
    global $PEANUT;
    $new = new Post();
    $date = time();
    $id = $PEANUT['flatfiles']->incrementId('posts');
    if ($id === false) {
      return false;
    }
    if (!isset($name)) { // Remove all non-alphanumeric characters, replace whitespaces with dashes and convert to lowercase
      $name = strtolower(
        preg_replace(
          '/[ \-]/',
          '-',
          preg_replace('/[^(a-zA-Z0-9 \-)]/', '', $title)
        )
      );
    }
    if ($PEANUT['configuration']->get('commentingDefault') == 'on'
        AND (!isset($commenting) OR $commenting == 'off')
        OR (isset($commenting) AND $commenting == 'on')) {
      $commenting = 'on';
    }
    else {
      $commenting = 'off';
    }
    $new->id = $id;
    $new->name = $name;
    $new->title = $title;
    $new->date = $date;
    $new->state = $state;
    $new->commenting = $commenting;
    $new->comments = 0;
    $new->content = $content;
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
      $new->addTag(Tag::create($tag));
    }
    return $new;
  }

  public static function getById($id) {
    global $PEANUT;
    if (isset(self::$cache[$id])) {
      return self::$cache[$id];
    }
    $obj = new self();
    $row = $PEANUT['flatfiles']->getRow('posts', $id);
    if ($row == FALSE) {
      throw new PostNotFoundException(tr('A post with id "%1" was not found.', $id));
    }
    $obj->id = $id;
    foreach ($row as $column => $value) {
      $obj->$column = $value;
    }
    return $obj;
  }

  public static function getByName($name) {
    global $PEANUT;
    $id = $PEANUT['flatfiles']->indexFind('posts', 'name', $name);
    if ($id == FALSE) {
      throw new PostNotFoundException(tr('A post with name "%1" was not found.', $name));
    }
    return self::getById($id);
  }
  
  public function addTag(Tag $tag) {
    global $PEANUT;
    $PEANUT['flatfiles']->addRelation('tags', 'posts', $tag->id, $this->id);
  }
  
  public function removeTag(Tag $tag) {
    global $PEANUT;
    $PEANUT['flatfiles']->removeRelation('tags', 'posts', $tag->id, $this->id);
  }

  public static function select(Selector $selector = null) {
    $selectHelper = new SelectHelper(get_class(), 'posts');
    $selectHelper->defaultSelector->orderBy('date')->desc();
    return $selectHelper->select($selector);
  }

  public function formatTime($format = null) {
    global $PEANUT;
    if (!isset($format)) {
      $format = $PEANUT['i18n']->timeFormat();
    }
    return $PEANUT['i18n']->date($format, $this->date);
  }

  public function formatDate($format = null) {
    global $PEANUT;
    if (!isset($format)) {
      $format = $PEANUT['i18n']->dateFormat();
    }
    return $PEANUT['i18n']->date($format, $this->date);
  }

  public function commit() {
    if (!$this->updated) {
      return;
    }
    echo 'Updating database';
  }

  public function delete() {
    echo 'Deletig?';
  }

}


/* Exceptions */

class PostNotFoundException extends Exception {}