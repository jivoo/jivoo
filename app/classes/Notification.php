<?php
abstract class Notification {

  private $uid;

  private $session;
  private $message;

  public function __get($property) {
    switch ($property) {
      case 'uid':
        return $this->uid;
      case 'message':
        return $this->message;
      case 'type':
        return $this->getType();
      case 'label':
        return $this->getLabel();
      default:
        throw new Exception(tr('Invalid property'));
    }
  }

  public function __construct($message, $uid = NULL, $readMore = NULL) {
    $this->session = new Session(SESSION_PREFIX);
    $type = get_class($this);
    $this->message = $message;
    if (!isset($uid)) {
      $uid = md5($message);
    }
    $this->uid = $uid;
    if (!isset($this->session['notifications'])
        OR !is_array($this->session['notifications'])) {
      $this->session['notifications'] = array();
    }
    $this->session['notifications'][$uid] = $this;
  }

  public function delete() {
    unset($this->session['notifications'][$this->uid]);
  }

  public static function all() {
    $type = get_called_class();
    $result = array();
    if (!isset($this->session['notifications'])) {
      return $result;
    }
    foreach ($this->session['notifications'] as $uid => $obj) {
      if (is_a($obj, $type) OR is_subclass_of($obj, $type)) {
        $result[] = $obj;
      }
    }
    return $result;
  }

  public static function count() {
    $type = get_called_class();
    $result = 0;
    if (!isset($this->session['notifications'])) {
      return 0;
    }
    foreach ($this->session['notifications'] as $uid => $obj) {
      if (is_a($obj, $type) OR is_subclass_of($obj, $type)) {
        $result++;
      }
    }
    return $result;
  }

  private function getLabel() {
    $type = get_class($this);
    switch ($type) {
      case 'LocalError':
      case 'GlobalError':
        return tr('Error');
      case 'LocalWarning':
      case 'GlobalWarning':
        return tr('Warning');
      case 'LocalNotice':
      case 'GlobalNotice':
      default:
        return tr('Notice');
    }
  }

  private function getType() {
    $type = get_class($this);
    switch ($type) {
      case 'LocalError':
      case 'GlobalError':
        return 'error';
      case 'LocalWarning':
      case 'GlobalWarning':
        return 'warning';
      case 'LocalNotice':
      case 'GlobalNotice':
      default:
        return 'notice';
    }
  }
}

