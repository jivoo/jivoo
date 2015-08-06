<?php
// Jivoo
// Copyright (c) 2015 Niels Sonnich Poulsen (http://nielssp.dk)
// Licensed under the MIT license.
// See the LICENSE file or http://opensource.org/licenses/MIT for more information.
namespace Jivoo\Core;

/**
 * A producer of events. Handlers and listeners can be attached.
 */
interface IEventSubject {
  /**
   * Attach an event handler to an event.
   * @param string $name Name of event to handle.
   * @param callback $callback Function to call. Function must accept an
   * {@see Event) as its first parameter.
   */
  public function attachEventHandler($name, $callback);
  
  /**
   * Attach an event listener to object (i.e. multiple handlers to multiple
   * events).
   * @param IEventListener $listener An event listener.
   */
  public function attachEventListener(IEventListener $listener);
  
  /**
   * Detach an already attached event handler.
   * @param string $name Name of event.
   * @param callback $callback Function to detach from event.
   */
  public function detachEventHandler($name, $callback);
  
  /**
   * Detach all handlers implemented by an event listener.
   * @param IEventListener $listener An event listener.
   */
  public function detachEventListener(IEventListener $listener);
  
  /**
   * Whether or not the object produces the given event.
   * @param string $name Name of event.
   * @return bool True if object produces event, false otherwise.
   */
  public function hasEvent($name);

  /**
   * Get names of all events produced by object.
   * @return string[] List of event names.
   */
  public function getEvents();
}