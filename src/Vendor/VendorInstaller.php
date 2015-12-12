<?php
// Jivoo
// Copyright (c) 2015 Niels Sonnich Poulsen (http://nielssp.dk)
// Licensed under the MIT license.
// See the LICENSE file or http://opensource.org/licenses/MIT for more information.
namespace Jivoo\Vendor;

use Jivoo\Core\Paths;
use Jivoo\Core\App;
use Jivoo\Autoloader;

/**
 * Installs third-party libraries using build scripts and repositories. 
 */
class VendorInstaller {
  /**
   * @var App
   */
  private $app;

  /**
   * @var Repository[]
   */
  private $repositories = array();
  
  public function __construct(App $app) {
    $this->app = $app;
  }
  
  /**
   * Add a vendor repository.
   * @param string $name Name.
   * @param Repository $repository Repository.
   */
  public function addRepository($name, Repository $repository) {
    $this->repositories[$name] = $repository; 
  }
  
  /**
   * Remove a vendor repository.
   * @param string $name Name.
   */
  public function removeRepository($name) {
    if (isset($this->repositories[$name]))
      unset($this->repositories[$name]);
  }
  
  public function getRepositories() {
    return $this->repositories;
  }
  
  public function getPackages() {
    $packages = array();
    foreach ($this->repositories as $name => $repository) {
      $packages[$name] = $repository->getPackages();
    }
    return $packages;
  }
  
  public function search($query) {
    $packages = array();
    foreach ($this->repositories as $name => $repository) {
      $packages[$name] = array();
      foreach ($repository->getPackages() as $package) {
        $match = true;
        foreach ($query as $word) {
          if (stripos($package, $word) === false) {
            $match = false;
            break;
          }
        }
        if ($match)
          $packages[$name][] = $package;
      }
    }
    return $packages;
  }
}