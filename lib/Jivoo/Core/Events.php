<?php
/**
 * Collection of events and handlers
 * @package Core
 */
class Events {
  /**
   * @var array Associative array where the key is an event name and the value
   * is an array of callbacks
   */
  private $events = array();

  /**
   * @var object The object that triggers events in this collection
   */
  private $sender = null;

  /**
   * Create a new event collection
   * @param object $sender The object that triggers events in this collection
   */
  public function __construct($sender) {
    $this->sender = $sender;
  }

  /**
   * Attach a handler to an event.
   * 
   * This function has to be called from an event-function.
   * The name of the function will be the name of the event.
   * @param callback $handler A function of type
   * `function eventHandler($object, $eventArgs)`
   */
  public function attach($handler = null) {
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

  /**
   * Execute all functions attached to an event
   * @param string $event Event name
   * @param mixed $eventArgs Event arguments
   */
  public function trigger($event, $eventArgs = null) {
    if (isset($this->events[$event])) {
      foreach ($this->events[$event] as $function) {
        call_user_func($function, $this->sender, $eventArgs);
      }
    }
  }
}

