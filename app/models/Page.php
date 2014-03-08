<?php

class Page extends ActiveModel {
  protected $mixins = array('Timestamps');

  protected $belongsTo = array(
    'User'
  );

  protected $validate = array(
    'title' => array('presence' => true, 'minLength' => 4, 'maxLength' => 25,),
    'name' => array('presence' => true, 'minLength' => 1, 'maxLength' => 25,
      'rule0' => array('match' => '/^[a-z-\/]+$/',
        'message' => 'Only lowercase letters, numbers, dashes and slashes allowed.'
      ),
    ), 'content' => array('presence' => true,),
  );

  protected $labels = array(
    'title' => 'Title',
    'name' => 'Permalink',
    'content' => 'Content',
  );

  public function getRoute(ActiveRecord $record) {
    return array(
      'controller' => 'Pages',
      'action' => 'view',
      'parameters' => array($record->id)
    );
  }
}
