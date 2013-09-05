<?php
class User extends ActiveRecord implements ILinkable {

  protected $hasMany = array(
    'Post',
    'Comment',
    'UserSession',
  );

  protected $belongsTo = array(
    'Group',
  );

  protected $validate = array(
    'username' => array('presence' => true,),
    'password' => array('presence' => true,),
    'email' => array('presence' => true, 'email' => true),
    'confirm_password' => array('presence' => true,
      'ruleConfirm' => array('callback' => 'confirmPassword',
        'message' => 'The two passwords are not identical'
      ),
    ),
  );

  protected $fields = array(
    'username' => 'Username',
    'email' => 'Email',
    'password' => 'Password',
    'confirm_password' => 'Confirm password',
  );

  protected $defaults = array(
    'ip' => '',
    'group_id' => 0,
  );

  public function getRoute() {
    return array(
      'controller' => 'Users',
      'action' => 'view',
      'parameters' => array($this->username)
    );
  }

  public function hasPermission($key) {
    $group = $this->getGroup();
    return $group AND $group->hasPermission($key);
  }

  protected function confirmPassword($value) {
    return $value == $this->password;
  }
}
