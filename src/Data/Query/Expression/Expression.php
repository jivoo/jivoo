<?php
// Jivoo
// Copyright (c) 2015 Niels Sonnich Poulsen (http://nielssp.dk)
// Licensed under the MIT license.
// See the LICENSE file or http://opensource.org/licenses/MIT for more information.
namespace Jivoo\Data\Query;

/**
 * A condition for selecting rows in a database table
 * @method Expression and(Expression|string $clause, mixed $vars,... ) AND operator
 * @method Expression or(Expression|string $clause, mixed $vars,... ) OR operator
 */
interface Expression {
  public function toString(Quoter $quoter);

  /**
   * Implements methods {@see Condition::and()} and {@see Condition::or()}
   * @param string $method Method name ('and' or 'or')
   * @param mixed[] $args List of parameters
   * @return Expression Expression.
   */
  public function __call($method, $args);

  /**
   * Add clause with AND operator
   * @param Expression|string $clause Clause
   * @param mixed $vars,... Additional values to replace placeholders in
   * $clause with
   * @return Expression Expression.
   */
  public function where($clause);

  /**
   * Add clause with AND operator
   * @param Expression|string $clause Clause
   * @param mixed $vars,... Additional values to replace placeholders in
   * $clause with
   * @return Expression Expression.
   */
  public function andWhere($clause);

  /**
   * Add clause with OR operator
   * @param Expression|string $clause Clause
   * @param mixed $vars,... Additional values to replace placeholders in
   * $clause with
   * @return Expression Expression.
   */
  public function orWhere($clause);
}
