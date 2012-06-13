<?php
// Module
// Name           : Users
// Version        : 0.2.0
// Description    : The PeanutCMS user system
// Author         : PeanutCMS
// Dependencies   : errors configuration templates actions database routes http

/*
 * Static pages
 *
 * @package PeanutCMS
 */

/**
 * Pages class
 */
class Users implements IModule{

  private $core;
  private $errors;
  private $configuration;
  private $actions;
  private $database;
  private $routes;
  private $templates;
  private $http;

  private $user;

  private $hashTypes = array(
    'sha512',
    'sha256',
    'blowfish',
    'md5',
    'ext_des',
    'std_des'
  );

  public function __construct(Core $core) {
    $this->core = $core;
    $this->database = $this->core->database;
    $this->actions = $this->core->actions;
    $this->routes = $this->core->routes;
    $this->http = $this->core->http;
    $this->templates = $this->core->templates;
    $this->errors = $this->core->errors;
    $this->configuration = $this->core->configuration;

    if (!ActiveRecord::isConnected()) {
      throw new Exception('temporary.');
    }

    $newInstall = FALSE;

    require_once(p(MODELS . 'User.php'));

    if (!$this->database->tableExists('users')) {
      $this->database->createQuery('users')
        ->addInt('id', TRUE, TRUE)
        ->setPrimaryKey('id')
        ->addVarchar('username', 255)
        ->addVarchar('password', 255)
        ->addVarchar('email', 255)
        ->addVarchar('session', 255)
        ->addVarchar('cookie', 255)
        ->addVarchar('ip', 255)
        ->addInt('group_id', TRUE)
        ->addIndex(TRUE, 'username')
        ->addIndex(TRUE, 'email')
        ->execute();
    }

    ActiveRecord::addModel('User', 'users');

    require_once(p(MODELS . 'Group.php'));

    if (!$this->database->tableExists('groups')) {
      $this->database->createQuery('groups')
      ->addInt('id', TRUE, TRUE)
      ->setPrimaryKey('id')
      ->addVarchar('name', 255)
      ->addVarchar('title', 255)
      ->addIndex(TRUE, 'name')
      ->execute();
      $newInstall = TRUE;
    }

    ActiveRecord::addModel('Group', 'groups');

    if (!$this->database->tableExists('groups_permissions')) {
      $this->database->createQuery('groups_permissions')
      ->addInt('group_id', TRUE, TRUE)
      ->addVarchar('permission', 255)
      ->setPrimaryKey('group_id', 'permission')
      ->execute();
    }

    if ($newInstall) {
      $group = Group::create();
      $group->name = 'root';
      $group->title = tr('Admin');
      $group->save();
      $group->setPermission('*', TRUE);

      $group = Group::create();
      $group->name = 'users';
      $group->title = tr('User');
      $group->save();
      $group->setPermission('content.comments.create', TRUE);

      $group = Group::create();
      $group->name = 'guests';
      $group->title = tr('Guest');
      $group->save();
      $group->setPermission('content.comments.create', TRUE);
    }

    if (!$this->configuration->exists('users.defaultGroups.unregistered')) {
      $this->configuration->set('users.defaultGroups.unregistered', 'guests');
    }

    if (!$this->configuration->exists('users.defaultGroups.registered')) {
      $this->configuration->set('users.defaultGroups.registered', 'users');
    }

    if (!$this->configuration->exists('users.hashType')) {
      foreach ($this->hashTypes as $hashType) {
        $constant = 'CRYPT_' . strtoupper($hashType);
        if (defined($constant) AND constant($constant) == 1) {
          $this->configuration->set('users.hashType', $hashType);
          break;
        }
      }
    }

    if ($this->actions->has('logout')) {
      $this->logOut();
      $this->http->refreshPath();
    }

  }

  public function genSalt($hashType = NULL) {
    if (!isset($hashType)) {
      $hashType = $this->configuration->get('users.hashType');
    }
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789./';
    $max = strlen($chars) - 1;
    $salt = '';
    switch (strtolower($hashType)) {
      case 'sha512':
        $saltLength = 16;
        // rounds from 1000 to 999,999,999
        $prefix = '$6$rounds=999999999';
        break;
      case 'sha256':
        $saltLength = 16;
        // rounds from 1000 to 999,999,999
        $prefix = '$5$rounds=5000$';
        break;
      case 'blowfish':
        $saltLength = 22;
        // cost (second param) from 04 to 31
        $prefix = '$2a$08$';
        break;
      case 'md5':
        $saltLength = 8;
        $prefix = '$1$';
        break;
      case 'ext_des':
        $saltLength = 4;
        // iterations (4 characters after _) from .... to zzzz
        $prefix = '_J9..';
        break;
      case 'std_des':
      default:
        $saltLength = 2;
        $prefix = '';
        break;
    }
    for ($i = 0; $i < $saltLength; $i++) {
      $salt .= $chars[mt_rand(0, $max)];
    }
    return $prefix . $salt;
  }

  public function hash($string, $hashType = NULL) {
    return crypt($string, $this->genSalt($hashType));
  }

  public function compareHash($string, $hash) {
    return crypt($string, $hash) == $hash;
  }

  public function getLink(User $record) {
    return $this->http->getLink($record->getPath());
  }

  public function isLoggedIn() {
    if (isset($this->user)) {
      return TRUE;
    }
    if ($this->checkSession()) {
      return TRUE;
    }
    if ($this->checkCookie()) {
      return TRUE;
    }
    return FALSE;
  }

  public function getUser() {
    return $this->isLoggedIn() ? $this->user : FALSE;
  }

  protected function checkSession() {
    if (isset($_SESSION[SESSION_PREFIX . 'username'])) {
      $sid = session_id();
      $ip = $_SERVER['REMOTE_ADDR'];
      $username = $_SESSION[SESSION_PREFIX . 'username'];
      $user = User::first(
        SelectQuery::create()
          ->where('username = ? AND session = ? AND ip = ?')
          ->addVar($username)
          ->addVar($sid)
          ->addVar($ip)
      );
      if ($user) {
        $this->user = $user;
        return TRUE;
      }
    }
    return FALSE;
  }

  protected function checkCookie() {
    if (isset($_COOKE[SESSION_PREFIX . 'login'])) {
      list($username, $cookie) = explode(':', $_COOKIE[SESSION_PREFIX . 'login']);
      $user = User::first(
          SelectQuery::create()
          ->where('username = ? AND cookie = ?')
          ->addVar($username)
          ->addVar($cookie)
      );
      if ($user) {
        $this->user = $user;
        return TRUE;
      }
      else {
        setcookie(SESSION_PREFIX . 'login', '', time(), WEBPATH);
        unset($_COOKIE[SESSION_PREFIX . 'login']);
      }
    }
    return FALSE;
  }

  protected function setSession($remember = FALSE) {
    session_regenerate_id();
    $sid = session_id();
    $ip = $_SERVER['REMOTE_ADDR'];
    $username = $this->user->username;
    $cookie = $this->user->cookie;
    $_SESSION[SESSION_PREFIX . 'username'] = $username;
    if ($remember) {
      $cookie = md5($username . rand() . time());
      $cookieval = implode(':', array($username, $cookie));
      setcookie(SESSION_PREFIX . 'login', $cookieval, time()+60*60*24*365, WEBPATH);
    }
    $this->user->session = $sid;
    $this->user->cookie = $cookie;
    $this->user->ip = $ip;
    $this->user->save();

  }

  public function logIn($username, $password, $remember = FALSE) {
    $user = User::first(
      SelectQuery::create()
        ->where('username = ?')
        ->addVar($username)
    );
    if (!$user) {
      return FALSE;
    }
    if (!$this->compareHash($password, $user->password)) {
      return FALSE;
    }
    $this->user = $user;
    $this->setSession($remember);
    return TRUE;
  }

  public function logOut() {
    $this->sessionDefaults();
    if (isset($_COOKIE[SESSION_PREFIX . 'login'])) {
      setcookie(SESSION_PREFIX . 'login', '', time(), WEBPATH);
      unset($_COOKIE[SESSION_PREFIX . 'login']);
    }
    $this->user = NULL;
  }

  protected function sessionDefaults() {
    $_SESSION[SESSION_PREFIX . 'username'] = '';
  }
}
