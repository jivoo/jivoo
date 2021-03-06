<?php
// Jivoo
// Copyright (c) 2015 Niels Sonnich Poulsen (http://nielssp.dk)
// Licensed under the MIT license.
// See the LICENSE file or http://opensource.org/licenses/MIT for more information.
namespace Jivoo\Vendor;

use Jivoo\Core\App;
use Jivoo\Core\Cli\CommandBase;

/**
 * Shell command for vendor packages.
 */
class VendorCommand extends CommandBase {
  /**
   * Construct
   * @param App $app Application.
   */
  public function __construct(App $app) {
    parent::__construct($app);
    
    $this->addCommand('update', array($this, 'update'), tr('Update one or more libraries'));
    $this->addCommand('install', array($this, 'install'), tr('Download and install libraries'));
    $this->addCommand('remove', array($this, 'remove'), tr('Remove a library'));
    $this->addCommand('info', array($this, 'info'), tr('Show library info'));
    $this->addCommand('list', array($this, 'list_'), tr('List installed/available libraries'));
    $this->addCommand('search', array($this, 'search'), tr('Search for libraries in available repositories'));
    
    $this->addOption('user');
    $this->addOption('share');
  }
  
  /**
   * {@inheritdoc}
   */
  public function getDescription($option = null) {
    return tr('Manage third-party libraries');
  }
  
  public function list_($paramters, $options) {
    
  }
  
  /**
   * @param string $dir
   */
  private function removeDir($dir) {
    $files = scandir($dir);
    foreach ($files as $file) {
      if ($file != '.' and $file != '..') {
        $path = $dir . '/' . $file;
        if (is_dir($path))
          $this->removeDir($path);
        else
          unlink($path);
      }
    }
    rmdir($dir);
  }
  
  /**
   * Package info.
   * @param string[] $parameters Parameters.
   * @param string[] $options Options.
   */
  public function info($parameters, $options) {
    if (!count($parameters)) {
      $this->put('usage: vendor info NAME');
      return;
    }
    $name = $parameters[0];
    $package = $this->m->vendor->getPackage($name);
    if (!isset($package)) {
      $this->error('Package not found: ' . $name);
      return;
    }
    $this->put($package->getName(), '');
    $manifest = $package->getManifest();
    if (isset($manifest['version'])) {
      $this->put(' ' . $manifest['version'], '');
    }
    $this->put();
  }
  
  
  /**
   * Remove package.
   * @param string[] $parameters Parameters.
   * @param string[] $options Options.
   */
  public function remove($parameters, $options) {
    if (!count($parameters)) {
      $this->put('usage: vendor remove [--user|--share] NAME');
      return;
    }
    $name = $parameters[0];
    if (isset($options['user']))
      $dir = $this->p('vendor/' . $name);
    else if (isset($options['share']))
      $dir = $this->p('share/vendor/' . $name);
    else
      $dir = $this->p('app/vendor/' . $name);
    if (!file_exists($dir)) {
      $this->error('Directory not found: ' . $dir);
      return;
    }
    $this->put(tr('The following directory will be deleted:'));
    $this->put('  - ' . $dir);
    $this->put();
    $confirm = $this->confirm(tr('Remove %1?', $name), true);
    if ($confirm) {
      $this->put(tr('Removing %1...', $name));
      $this->removeDir($dir);
    }
  }
  
  /**
   * Update package(s).
   * @param string[] $parameters Parameters.
   * @param string[] $options Options.
   */
  public function update($parameters, $options) {
    $packages = $this->m->vendor->getPackages();
    foreach ($packages as $manifest) {
      $this->put('Updating ' . $manifest->getName() . '...');
    }
  }
  
  /**
   * Install package..
   * @param string[] $parameters Parameters.
   * @param string[] $options Options.
   */
  public function install($parameters, $options) {
    if (!count($parameters)) {
      $this->put('usage: vendor install [--user|--share] NAME [NAMES...]');
      return;
    }
    foreach ($parameters as $name) {
      $script = $this->m->vendorInstaller->getBuildScript($name);
      if (!isset($script)) {
        $this->error('Build script for ' . $name . ' not found');
        return;
      }
      $this->put('Building ' . $script->name . ' ' . $script->version . '...');
      if (isset($options['user']))
        $dest = $this->p('vendor');
      else if (isset($options['share']))
        $dest = $this->p('share/vendor');
      else
        $dest = $this->p('app/vendor');
      $script->run($dest);
    }
  }
  
  /**
   * Search for package.
   * @param string[] $parameters Parameters.
   * @param string[] $options Options.
   */
  public function search($parameters, $options) {
    $results = $this->m->vendorInstaller->search($parameters);
    foreach ($results as $repo => $packages) {
      foreach ($packages as $package) {
        $this->put($package, '');
        $this->put(' (' . $repo . ')', '');
        if ($this->vendor->isInstalled($package))
          $this->put(' [installed]', '');
        $this->put();
      }
    }
  }
}
