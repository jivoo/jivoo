<?php
/**
 * Automatically generated schema for links table
 * @package PeanutCMS
 * @subpackage Schemas
 */
class linksSchema extends Schema {
  public $id = array(
    'type' => 'integer',
    'unsigned' => true,
    'length' => 10,
    'key' => 'primary',
    'autoIncrement' => true,
    'null' => false,
  );

  public $menu = array(
    'type' => 'string',
    'length' => 255,
    'key' => 'index',
    'null' => false,
  );

  public $type = array(
    'type' => 'string',
    'length' => 10,
    'null' => false,
  );

  public $title = array(
    'type' => 'string',
    'length' => 255,
    'null' => false,
  );

  public $path = array(
    'type' => 'text',
    'null' => false,
  );

  public $position = array(
    'type' => 'integer',
    'length' => 10,
    'default' => '0',
    'null' => false,
  );

  public $indexes = array(
    'PRIMARY' => array(
      'columns' => array('id'),
      'unique' => true
    ),
    'menu' => array(
      'columns' => array('menu'),
      'unique' => false
    ),
  );
}
