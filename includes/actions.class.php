<?php
/*
 * Class for acting on url actions
 *
 * @package PeanutCMS
 */

/**
 * Actions class
 */
class Actions {

  /**
   * Constructor
   */
  function Actions() {
    return $this->__construct();
  }

  /**
   * PHP5-style constructor
   */
  function __construct() {
    global $PEANUT;
  }

  /**
   * PHP5-style destructor
   *
   * @return bool true
   */
  function __destruct() {
    return true;
  }

  /**
   * Check if an action is present in the url and/or post data
   *
   * @param string $action Action
   * @param string $getPost Optional; confine to GET ('get') or POST ('post'), default is 'both'
   * @return bool
   */
  function has($action, $getPost = 'both') {
    global $PEANUT;
    if ($getPost != 'post' AND $getPost != 'sessionget' AND isset($PEANUT['http']->params[$action])) {
      unset($PEANUT['http']->params[$action]);
      return true;
    }
    if ($getPost != 'get' AND $getPost != 'sessionget' AND isset($_POST['action']) AND $_POST['action'] == $action) {
      return true;
    }
    if ($getPost == 'sessionget' AND isset($_SESSION[SESSION_PREFIX . 'action']) AND $_SESSION[SESSION_PREFIX . 'action'] == $action) {
      unset($_SESSION[SESSION_PREFIX . 'action']);
      if (isset($PEANUT['http']->params[$action])) {
        unset($PEANUT['http']->params[$action]);
        return true;
      }
    }
    return false;
  }

  function add($action) {
    global $PEANUT;
    unset($_GET[$action]);
    return $PEANUT['http']->getLink(null, array_merge($_GET, array($action => '')));
  }

}