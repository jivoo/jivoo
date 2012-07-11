<?php

class Comment extends ActiveRecord implements ILinkable {

  protected $hasMany = array(
    'Reply' => array('class' => 'Comment',
                     'plural' => 'Replies',
                     'connection' => 'other',
                     'thisKey' => 'parent_id')
  );

  protected $belongsTo = array(
    'Post' => array('connection' => 'this',
                    'otherKey' => 'post_id'),
    'Parent' => array('class' => 'Comment',
                      'connection' => 'this',
                      'otherKey' => 'parent_id'),
    'User' => array('connection' => 'this',
                    'otherKey' => 'user_id')
  );
  
  protected $validate = array(
    'content' => array(
      'presence' => TRUE,
    ),
  );
  
  protected $labels = array(
    'author' => 'Name',
    'email' => 'E-mail',
    'website' => 'Website',
    'content' => 'Content'
  );

  protected $defaults = array(
    'user_id' => 0,
    'parent_id' => 0,
    'date' => array('time'),
    'email' => '',
    'website' => ''
  );

  public function getRoute() {
    return array(
      'controller' => 'Posts',
      'action' => 'viewComment',
      'parameters' => array($this->post_id, $this->id)
    );
  }

  public function formatDate() {
    return fdate($this->date);
  }

  public function formatTime() {
    return ftime($this->date);
  }
}
