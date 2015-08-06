<?php
// Jivoo
// Copyright (c) 2015 Niels Sonnich Poulsen (http://nielssp.dk)
// Licensed under the MIT license.
// See the LICENSE file or http://opensource.org/licenses/MIT for more information.

/**
 * Translate function alias.
 * @see I18n::get()
 */
function tr($message) {
  $args = func_get_args();
  return call_user_func_array(array('Jivoo\Core\I18n', 'get'), $args);
}

/**
 * Translate function alias.
 * @see I18n::getNumeric()
 */
function tn($message, $singular, $number) {
  $args = func_get_args();
  return call_user_func_array(array('Jivoo\Core\I18n', 'getNumeric'), $args);
}

/**
 * Encode string for HTML usage.
 * @param string $string Input.
 * @return string Output.
 */
function h($string) {
  return htmlentities(strval($string), ENT_COMPAT, 'UTF-8');
}

/**
 * Alias for Condition constructor.
 * @param ICondition|string Condition.
 * @return Condition Condition object.
 */
function where($condition) {
  $args = func_get_args();
  $condition = new Jivoo\Models\Condition\Condition();
  call_user_func_array(array($condition, 'andWhere'), $args);
  return $condition;
}

/**
 * @see I18n::formatDate()
 */
function fdate($timestamp = null) {
  return Jivoo\Core\I18n::formatDate($timestamp);
}

/**
 * @see I18n::formatTime()
 */
function ftime($timestamp = null) {
  return Jivoo\Core\I18n::formatTime($timestamp);
}

/**
 * @see I18n::longDate()
 */
function ldate($timestamp = null) {
  return Jivoo\Core\I18n::longDate($timestamp);
}

/**
 * @see I18n::shortDate()
 */
function sdate($timestamp = null) {
  return Jivoo\Core\I18n::shortDate($timestamp);
}

/**
 * @see I18n::date()
 */
function tdate($format, $timestamp = null) {
  return Jivoo\Core\I18n::date($format, $timestamp);
}

/**
 * Precondition function that can be used to add additional constraints to
 * function parameters.
 * @param bool $condition Condition.
 * @param string $message Failure message.
 * @throws \Jivoo\InvalidArgumentException If condition is false.
 */
function assume($condition, $message = null) {
  if ($condition === true) {
    return;
  }
  if (isset($message))
    throw new Jivoo\InvalidArgumentException($message);
  $bt = debug_backtrace();
  $call = $bt[0];
  $lines = file($call['file']);
  preg_match(
    '/' . $call['function'] . '\((.+)\)/',
    $lines[$call['line'] - 1],
    $matches
  );
  throw new Jivoo\InvalidArgumentException(tr('Assumption failed: %1', $matches[1]));
}