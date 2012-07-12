<?php
/**
 * Automatically generated schema for comments table
 */
class commentsSchema extends Schema {
  public $id = array(
    'type' => 'integer',
    'length' => 10,
    'key' => 'primary',
    'autoIncrement' => true,
    'null' => false,
  );

  public $post_id = array(
    'type' => 'integer',
    'length' => 10,
    'key' => 'index',
    'null' => false,
  );

  public $user_id = array(
    'type' => 'integer',
    'length' => 10,
    'default' => '0',
    'null' => false,
  );

  public $parent_id = array(
    'type' => 'integer',
    'length' => 10,
    'default' => '0',
    'null' => false,
  );

  public $author = array(
    'type' => 'string',
    'length' => 255,
    'null' => false,
  );

  public $email = array(
    'type' => 'string',
    'length' => 255,
    'null' => false,
  );

  public $website = array(
    'type' => 'string',
    'length' => 255,
    'null' => false,
  );
  
  public $ip = array(
    'type' => 'string',
    'length' => 255,
    'null' => false,
  );

  public $content = array(
    'type' => 'text',
    'null' => false,
  );

  public $date = array(
    'type' => 'integer',
    'length' => 10,
    'null' => false,
  );

  public $indexes = array(
    'PRIMARY' => array(
      'columns' => array('id'),
      'unique' => true
    ),
    'post_id' => array(
      'columns' => array('post_id'),
      'unique' => false
    ),
  );
}
