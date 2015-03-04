<?php
// Jivoo
// Copyright (c) 2015 Niels Sonnich Poulsen (http://nielssp.dk)
// Licensed under the MIT license.
// See the LICENSE file or http://opensource.org/licenses/MIT for more information.
namespace Jivoo\Databases\Drivers\PdoMysql;

use Jivoo\Databases\Common\MysqlTypeAdapter;
use Jivoo\Databases\Common\PdoDatabase;
use Jivoo\Databases\DatabaseQueryFailedException;
use Jivoo\Databases\DatabaseConnectionFailedException;

/**
 * PDO MySQL database driver.
 */
class PdoMysqlDatabase extends PdoDatabase {
  /**
   * Construct database.
   * @param array $options An associative array with options for at least
   * 'server', 'username', 'password' and 'database'. 'tablePrefix' is optional.
   * @throws DatabaseConnectionFailedException If connection fails.
   */
  public function init($options = array()) {
    $this->setTypeAdapter(new MysqlTypeAdapter($this));
    if (isset($options['tablePrefix'])) {
      $this->tablePrefix = $options['tablePrefix'];
    }
    try {
      if (isset($options['password'])) {
        $this->pdo = new \PDO(
          'mysql:host=' . $options['server'] . ';dbname=' . $options['database'],
          $options['username'], $options['password']);
      }
      else {
        $this->pdo = new \PDO(
          'mysql:host=' . $options['server'] . ';dbname=' . $options['database'],
          $options['username']);
      }
    }
    catch (DatabaseQueryFailedException $exception) {
      throw new DatabaseConnectionFailedException($exception->getMessage());
    }
    catch (\PDOException $exception) {
      throw new DatabaseConnectionFailedException($exception->getMessage());
    }
  }
}
