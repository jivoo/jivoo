<?php
// Jivoo
// Copyright (c) 2015 Niels Sonnich Poulsen (http://nielssp.dk)
// Licensed under the MIT license.
// See the LICENSE file or http://opensource.org/licenses/MIT for more information.
namespace Jivoo\Core\Store;

use Jivoo\Core\Logger;
/**
 * An object allowing for the creation and reading of state-files in a directoy.
 */
class StateMap {
  /**
   * @var string
   */
  private $dir;
  
  /**
   * @var FileStore[]
   */
  private $files = array();
  
  /**
   * @var State[]
   */
  private $states = array();
  
  /**
   * Construct state map.
   * @param string $dir State directory.
   */
  public function __construct($dir) {
    $this->dir = $dir;
  }
  
  /**
   * Touch a state document (make sure that it exists).
   * @param string $key State document key.
   */
  public function touch($key) {
    if (!isset($this->files[$key])) {
      $this->files[$key] = new PhpStore($this->dir . '/' . $key . '.php');
      $this->files[$key]->touch();
    }
  }

  /**
   * Open a state document for reading. Remember to call {@see State::close()}
   * on the object when done!
   * @param string $key State document key.
   * @return State State document.
   */
  public function read($key) {
    if (isset($this->states[$key]) and $this->states[$key]->isOpen())
      return $this->states[$key];
    if (!isset($this->files[$key]))
      $this->touch($key);
    $this->states[$key] = new State($this->files[$key], false);
    Logger::debug(tr('Open state (read): %1', $key));
    return $this->states[$key];
  }

  /**
   * Open a state document for reading and writing. Remember to call
   * {@see State::close()} on the object when done!
   * @param string $key State document key.
   * @return State State document.
   */
  public function write($key) {
    if (isset($this->states[$key])) {
      if ($this->states[$key]->isMutable())
        return $this->states[$key];
      $this->close($key);
    }
    if (!isset($this->files[$key]))
      $this->touch($key);
    $this->states[$key] = new State($this->files[$key], true);
    Logger::debug(tr('Open state (write): %1', $key));
    return $this->states[$key];
  }
  
  /**
   * Whether a state document is open.
   * @param string $key State document key.
   * @return bool True if open.
   */
  public function isOpen($key) {
    return isset($this->states[$key]) and $this->states[$key]->isOpen();
  }
  
  /**
   * Whether a state document is open and mutable.
   * @param string $key State document key.
   * @return bool True if mutable.
   */
  public function isMutable($key) {
    return isset($this->states[$key]) and $this->states[$key]->isMutable();
  }
  
  /**
   * Close a state document.
   * @param string $key State document key.
   */
  public function close($key) {
    if (isset($this->states[$key]) and $this->states[$key]->isOpen())
      $this->states[$key]->close();
  }

  /**
   * Close all open state documents.
   * @return string[] List of keys of states that were closed.
   */
  public function closeAll() {
    $open = array();
    foreach ($this->states as $key => $state) {
      if ($state->isOpen()) {
        $state->close();
        $open[] = $key;
      }
    }
    return $open;
  }
}