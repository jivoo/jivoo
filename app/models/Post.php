<?php

class Post extends ActiveRecord implements ILinkable {

  protected $hasAndBelongsToMany = array(
  	'Tag' => array('join' => 'posts_tags',
  	               'otherKey' => 'tag_id',
  	               'thisKey' => 'post_id'),
  );

  protected $hasMany = array(
  	'Comment' => array('thisKey' => 'post_id',
                       'count' => 'comments'),
  );

  protected $belongsTo = array(
    'User' => array('connection' => 'this',
                    'otherKey' => 'user_id')
  );

  protected $hasOne = array(
    'Category' => array('class' => 'Tag')
  );
  
  protected $validate = array(
    'title' => array('presence' => true,
                     'maxLength' => 50),
    'name' => array('presence' => true,
                    'unique' => true,
                    'minLength' => 1,
                    'maxLength' => 50,
                    'match' => '/^[a-z0-9-]+$/'),
    'content' => array('presence' => true),
  );

  protected $defaults = array(
    'comments' => 0,
    'user_id' => 0
  );

  public function getLink() {
    return array(
      'controller' => 'Posts',
      'action' => 'view',
      'parameters' => array($this->id)
    );
  }

  public static function createName($title) {
    return strtolower(
      preg_replace(
        '/[ \-]/', '-', preg_replace(
          '/[^(a-zA-Z0-9 \-)]/', '', $title
        )
      )
    );
  }

  public function formatDate() {
    return fdate($this->date);
  }

  public function formatTime() {
    return ftime($this->date);
  }

  public function removeAllTags() {
    $tags = $this->getTags();
    foreach ($tags as $tag) {
      $this->removeTag($tag);
    }
  }

  public function createAndAddTags($csvTags) {
    $tags = explode(',', $csvTags);
    foreach ($tags as $title) {
      $title = trim($title);
      $name = Tag::createName($title);
      if ($title == '' OR $name == '') {
        continue;
      }
      $existing = Tag::first(
        SelectQuery::create()
          ->where('name = ?')
          ->addVar($name)
      );
      if ($existing !== FALSE) {
        $this->addTag($existing);
      }
      else {
        $tag = Tag::create();
        $tag->tag = $title;
        $tag->name = $name;
        $tag->save();
        $this->addTag($tag);
      }
    }
  }

  public function getCommentHierachy() {
    // recursive action here!!
  }
}

