<?php

class Comment extends ActiveRecord implements ILinkable {

  protected $hasMany = array(
    'Reply' => array('class' => 'Comment', 'plural' => 'Replies',
      'connection' => 'other', 'thisKey' => 'parent_id'
    )
  );

  protected $belongsTo = array(
    'Post' => array('connection' => 'this', 'otherKey' => 'post_id'),
    'Parent' => array('class' => 'Comment', 'connection' => 'this',
      'otherKey' => 'parent_id'
    ),
    'User' => array('connection' => 'this', 'otherKey' => 'user_id')
  );

  protected $validate = array(
    'content' => array('presence' => true, 'maxLength' => 1024,),
    'author' => array('presence' => true),
    'email' => array('presence' => true, 'email' => true),
    'website' => array('url' => true,),
  );

  protected $fields = array('author' => 'Name', 'email' => 'Email',
    'website' => 'Website', 'content' => 'Content', 'status' => 'Status',
    'date' => 'Date',
  );

  protected $defaults = array('date' => array('time'),
    'status' => 'unapproved', 'email' => '', 'website' => ''
  );

  public function getRoute() {
    return array('controller' => 'Posts', 'action' => 'viewComment',
      'parameters' => array($this->post_id, $this->id)
    );
  }

  public function formatDate() {
    return fdate($this->date);
  }

  public function formatTime() {
    return ftime($this->date);
  }

  protected function beforeValidate() {
    $encoder = new Encoder();
    $this->content_text = $encoder->encode($this->content);
  }

  protected function beforeSave($options) {
    if (!$options['validate']) {
      $this->beforeValidate();
    }
  }
}
