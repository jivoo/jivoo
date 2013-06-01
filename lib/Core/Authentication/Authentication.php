<?php
// Module
// Name           : Authentication
// Version        : 0.2.0
// Description    : The Apakoh Core authentication system
// Author         : apakoh.dk
// Dependencies   : Core/Shadow
//                  Core/Maintenance Core/Templates Core/Database
//                  Core/Routing

/**
 * Authentication module
 *
 * @package Core
 * @subpackage Authentication
 */
class Authentication extends ModuleBase {

  private $user = null;

  private $unregistered = null;

  protected function init() {
    $this->config->defaults = array(
      'defaultGroups' => array(
        'unregistered' => 'guests',
        'registered' => 'users',
      ),
      'rootCreated' => false, 
    );
    $newInstall = false;

    $usersSchema = new usersSchema();
    $groupsSchema = new groupsSchema();
    $groups_permissionsSchema = new groups_permissionsSchema();

    $this->m->Database->migrate($usersSchema);
    $newInstall = $this->m->Database->migrate($groupsSchema) == 'new';
    $this->m->Database->migrate($groups_permissionsSchema);

    $this->m->Database->users->setSchema($usersSchema);
    $this->m->Database->groups->setSchema($groupsSchema);
    $this->m->Database->groups_permissions
      ->setSchema($groups_permissionsSchema);

    User::connect($this->m->Database->users);
    Group::connect($this->m->Database->groups);

    $rootGroup = null;
    if ($newInstall) {
      $group = Group::create();
      $group->name = 'root';
      $group->title = tr('Admin');
      $group->save();
      $group->setPermission('*', true);
      $rootGroup = $group;

      $group = Group::create();
      $group->name = 'users';
      $group->title = tr('User');
      $group->save();
      $group->setPermission('frontend', true);

      $group = Group::create();
      $group->name = 'guests';
      $group->title = tr('Guest');
      $group->save();
      $group->setPermission('frontend', true);

    }

    if ($newInstall OR $this->config['rootCreated'] !== true) {
      Logger::debug('Authentication: No root user created');
      $controller = new AuthenticationController($this->m->Routing,
        $this->config);
      $controller->addModule($this->m->Shadow);
      $this->m->Maintenance->setup($controller, 'setupRoot', array($rootGroup));
    }

    if (!$this->isLoggedIn()) {
      $unregistered = Group::first(
        SelectQuery::create()
          ->where('name = ?', $this->config['defaultGroups']['unregistered'])
      );
      if ($unregistered) {
        $this->unregistered = $unregistered;
      }
    }
  }

  public function isLoggedIn() {
    if (isset($this->user)) {
      return true;
    }
    if ($this->checkSession()) {
      return true;
    }
    if ($this->checkCookie()) {
      return true;
    }
    return false;
  }

  public function hasPermission($permission) {
    if ($this->isLoggedIn()) {
      return $this->user->hasPermission($permission);
    }
    else if (isset($this->unregistered)) {
      return $this->unregistered->hasPermission($permission);
    }
    else {
      return false;
    }
  }

  public function getUser() {
    return $this->isLoggedIn() ? $this->user : false;
  }

  protected function checkSession() {
    if (isset($this->session['username'])) {
      $sid = session_id();
      $ip = $_SERVER['REMOTE_ADDR'];
      $user = User::first(
        SelectQuery::create()
          ->where('username = ?', $this->session['username'])
          ->and('session = ?', $sid)->and('ip = ?', $ip)
      );
      if ($user) {
        $this->user = $user;
        return true;
      }
    }
    return false;
  }

  protected function checkCookie() {
    if (isset($this->request->cookies['login'])) {
      list($username, $cookie) = explode(':', $this->request->cookies['login']);
      $user = User::first(
        SelectQuery::create()->where('username = ?', $username)
          ->and('cookie = ?', $cookie)
      );
      if ($user) {
        $this->user = $user;
        return true;
      }
      else {
        unset($this->request->cookies['login']);
      }
    }
    return false;
  }

  protected function setSession($remember = false) {
    session_regenerate_id();
    $sid = session_id();
    $ip = $_SERVER['REMOTE_ADDR'];
    $username = $this->user->username;
    $cookie = $this->user->cookie;
    $this->session['username'] = $username;
    if ($remember) {
      $cookie = md5($username . rand() . time());
      $cookieval = implode(':', array($username, $cookie));
      $this->request->cookies['login'] = $cookieval;
    }
    $this->user->session = $sid;
    $this->user->cookie = $cookie;
    $this->user->ip = $ip;
    if (!$this->user->save(array('validate' => false))) {
      throw new Exception(tr('Could not save user session data.'));
    }

  }

  public function logIn($username, $password, $remember = false) {
    $user = User::first(SelectQuery::create()->where('username = ?', $username));
    if (!$user) {
      return false;
    }
    if (!$this->m->Shadow->compare($password, $user->password)) {
      return false;
    }
    $this->user = $user;
    $this->setSession($remember);
    return true;
  }

  public function logOut() {
    $this->sessionDefaults();
    if (isset($this->cookies['login'])) {
      unset($this->request->cookies['login']);
    }
    $this->user = null;
  }

  protected function sessionDefaults() {
    unset($this->session['username']);
  }
}
