<?php

class Events {

  private $events = array();

  private $sender = NULL;

  public function __construct($sender) {
    $this->sender = $sender;
  }

  public function attach($handler = NULL) {
    $backtrace = debug_backtrace();
    if (isset($backtrace[1]['function'])) {
      if (!isset($handler)) {
        $handler = $backtrace[1]['args'][0];
      }
      $event = $backtrace[1]['function'];
      if (!isset($this->events[$event])) {
        $this->events[$event] = array();
      }
      $this->events[$event][] = $handler;
    }
  }

  public function trigger($event, $eventArgs = NULL) {
    if (isset($this->events[$event])) {
      foreach ($this->events[$event] as $function) {
        call_user_func($function, $this->sender, $eventArgs);
      }
    }
  }
}

