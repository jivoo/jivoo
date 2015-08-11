<?php
// Jivoo
// Copyright (c) 2015 Niels Sonnich Poulsen (http://nielssp.dk)
// Licensed under the MIT license.
// See the LICENSE file or http://opensource.org/licenses/MIT for more information.
namespace Jivoo\Core\I18n;

use Jivoo\Core\Unicode;

/**
 * Internationalization and localization.
 * @see Locale
 */
class I18n {
  
  /**
   * @var string
   */
  private static $language = 'en';
  
  /**
   * @var Locale
   */
  private static $locale = null;
  
  /**
   * Set current language.
   * @param string $language IETF language tag, e.g. 'en' or 'en-US'.
   */
  public static function setLanguage($language) {
    // regex source: http://www.w3.org/TR/xmlschema11-2/#language
    if (preg_match('/[a-z]{1,8}(-[a-z0-9]{1,8})*/i', $language) === 1) {
      self::$language = $language;
    }
    else {
      trigger_error(tr('Invalid language tag: %1', $language), E_USER_WARNING);
    }
  }


  /**
   * Get language code of current language.
   * @return string A language code, e.g. 'en', 'en-GB', 'da-DK', etc.
   */
  public static function getLanguage() {
    return self::$language;
  }
  
  /**
   * Get current localization object.
   * @return Locale Localization object.
   * @deprecated
   */
  public static function getLocalization() {
    if (!isset(self::$locale))
      self::$locale = new Locale();
    return self::$locale;
  }

  /**
   * Get current locale object.
   * @return Locale Locale object.
   */
  public static function getLocale() {
    if (!isset(self::$locale))
      self::$locale = new Locale();
    return self::$locale;
  }
  
  /**
   * Load a localization.
   * @param bool $extend Whether to extend the existing localization object
   * (true) or replace it (false).
   */
  public static function load(Locale $locale, $extend = true) {
    if (!isset(self::$locale))
      self::$locale = $locale;
    else
      self::$locale->extend($locale);
  }

  /**
   * Load a localization for the current language from a directory.
   * @param string $dir Directory path.
   * @param bool $extend Whether to extend the existing localization object
   * (true) or replace it (false).
   * @return bool True if language file found, false otherwise.
   */
  public static function loadFrom($dir, $extend = true) {
    $file = $dir . '/' . self::$language . '.';
    if (file_exists($file . 'mo')) {
      $localization = Locale::readMo($file . 'mo');
    }
    else if (file_exists($file . 'po')) {
      $localization = Locale::readPo($file . 'po');
    }
    else {
      trigger_error(tr('Language not found: %1', $file . 'mo'), E_USER_NOTICE);
      return false;
    }
    self::load($localization, $extend);
    return true;
  }

  /**
   * Translate a string.
   * @param string $message Message in english.
   * @param mixed $vars,... Values for placeholders starting from %1.
   * @return string Translated string.
   */
  public static function get($message) {
    $args = func_get_args();
    return call_user_func_array(array(self::getLocale(), 'get'), $args);
  }

  /**
   * Translate a string containing a numeric value.
   * 
   * For instance:
   * <code>
   * $l->nget('This post has %1 comments', 'This post has %1 comment', $numcomments);
   * </code>
   * 
   * @param string $message Message in english (plural).
   * @param string $singular Singular version of message in english.
   * @param mixed $vars,... Values for placholders starting from %1, the first one (%1) is the
   * numeral to test.
   * @return Translated string.
   */
  public static function nget($message, $singular, $number) {
    $args = func_get_args();
    return call_user_func_array(array(self::getLocale(), 'nget'), $args);
  }

  /**
   * Format a timestamp using preferred long format.
   * @param string $timestamp Timestamp, default is now.
   * @return string Formatted date and time.
   */
  public static function longDate($timestamp = null) {
    $l = self::getLocale();
    return self::date($l->longFormat, $timestamp);
  }

  /**
   * Format a timestamp using a short style.
   * @param string $timestamp Timestamp, default is now.
   * @return string Formatted date and time.
   */
  public static function shortDate($timestamp = null) {
    $l = self::getLocale();
    $cYear = date('Y');
    $date = date('Y-m-d', $timestamp);
    if (date('Y-m-d') == $date) {
      return tr('Today %1', self::formatTime($timestamp));
    }
    else if (date('Y-m-d', strtotime('yesterday')) == $date) {
      return tr('Yesterday %1', self::formatTime($timestamp));
    }
    else if (date('Y-m-d', strtotime('tomorrow')) == $date) {
      return tr('Tomorrow %1', self::formatTime($timestamp));
    }
    else if ($cYear == date('Y', $timestamp)) {
      return self::date($l->monthDay, $timestamp);
    }
    else {
      return self::date($l->monthYear, $timestamp);
    }
  }

  /**
   * Format date using the preferred date format of the current locale. 
   * @param int|null $timestamp UNIX timestamp or null for now 
   * @return string Formatted date string
   */
  public static function formatDate($timestamp = null) {
    $l = self::getLocale();
    return self::date($l->dateFormat, $timestamp);
  }

  /**
   * Format time using the preferred timeformat of the current locale. 
   * @param int|null $timestamp UNIX timestamp or null for now 
   * @return string Formatted time string
   */
  public static function formatTime($timestamp = null) {
    $l = self::getLocale();
    return self::date($l->timeFormat, $timestamp);
  }
  
  public static function number($number, $decimals = 0) {
    $l = self::getLocale();
    return number_format($number, $decimals, $l->decimalPoint, $l->thousandsSep);
  }

  /**
   * Localized date function
   *
   * Works like date() but translates month names and weekday names.
   *
   * @param string $format The format of the outputted date string. See {@link http://dk.php.net/manual/en/function.date.php date()}
   * @param int $timestamp Optional Unix timestamp to use. Default is value of time()
   * @return string Formatted date string
   */
  public static function date($format, $timestamp = null) {
    if (is_null($timestamp))
      $timestamp = time();
    $month = date('n', $timestamp);
    if ($month == 1)
      $F = tr('January');
    else if ($month == 2)
      $F = tr('February');
    else if ($month == 3)
      $F = tr('March');
    else if ($month == 4)
      $F = tr('April');
    else if ($month == 5)
      $F = tr('May');
    else if ($month == 6)
      $F = tr('June');
    else if ($month == 7)
      $F = tr('July');
    else if ($month == 8)
      $F = tr('August');
    else if ($month == 9)
      $F = tr('September');
    else if ($month == 10)
      $F = tr('October');
    else if ($month == 11)
      $F = tr('November');
    else if ($month == 12)
      $F = tr('December');
    $M = Unicode::slice($F, 0, 3);

    $weekday = date('w', $timestamp);
    if ($weekday == 0)
      $l = tr('Sunday');
    else if ($weekday == 1)
      $l = tr('Monday');
    else if ($weekday == 2)
      $l = tr('Tuesday');
    else if ($weekday == 3)
      $l = tr('Wednesday');
    else if ($weekday == 4)
      $l = tr('Thursday');
    else if ($weekday == 5)
      $l = tr('Friday');
    else if ($weekday == 6)
      $l = tr('Saturday');
    $D = Unicode::slice($l, 0, 3);
    $date = date($format, $timestamp);
    $date = str_replace(date('F', $timestamp), $F, $date);
    $date = str_replace(date('M', $timestamp), $M, $date);
    $date = str_replace(date('l', $timestamp), $l, $date);
    $date = str_replace(date('D', $timestamp), $D, $date);
    return $date;
  }
  

  /**
   * A localized implementation of {@see strtotime}. NOT YET IMPLEMENTED
   * @param string $str A date/time string.
   * @return int|null A UNIX timestamp or null on failure.
   */
  public static function stringToTime($str) {
    $l = self::getLocale();
    $date = \DateTime::createFromFormat($l->dateTimeFormat, $str);
    if (isset($date))
      return $date->getTimestamp();
    $time = strtotime($str);
    if ($time === false)
      return null;
    return $time;
  }

  /**
   * Converts a string to a timestamp like {@see stringToTime()} but returns an
   * interval (a 2-tuple array) instead, e.g.
   * the string "2014" is converted to a closed interval from 2014-01-01
   * 00:00:00 (as a UNIX timestamp) to 2014-12-31 23:59:59.
   * @param string $str A date/time string.
   * @return int[]|null A closed interval as a 2-tuple or null on failure.
   */
  public static function stringToInterval($str) {
    $tuple = self::parseDate($str);
    if (!isset($tuple))
      return null;
    list($year, $month, $day, $hour, $minute, $day, $precision) = $tuple;
    switch ($precision) {
      case 'year':
        return array(
          mktime(0, 0, 0, 1, 1, $year),
          mktime(23, 59, 59, 12, 31, $year)
        );
      case 'month':
        $start = mktime(0, 0, 0, $month, 1, $year);
        return array(
          $start,
          mktime(23, 59, 59, $month, idate('t', $start), $year)
        );
      case 'day':
        return array(
          mktime(0, 0, 0, $month, $day, $year),
          mktime(23, 59, 59, $month, $day, $year)
        );
      case 'hour':
        return array(
          mktime($hour, 0, 0, $month, $day, $year),
          mktime($hour, 59, 59, $month, $day, $year)
        );
      case 'minute':
        return array(
          mktime($hour, $minute, 0, $month, $day, $year),
          mktime($hour, $minute, 59, $month, $day, $year)
        );
      default:
        return array(
          mktime($hour, $minute, $second, $month, $day, $year),
          mktime($hour, $minute, $second, $month, $day, $year)
        );
    }
  }
  
  /**
   * Natural language date parser with precision, e.g. the string "june 2014"
   * results in the tuple: array(2014, 6, 1, 0, 0, 0, 'month').
   * @param string $str A date/time string
   * @return array|null A 7-tuple consisting of integers year, month, day,
   * hour, minute, second. The last element is a string defining the precision
   * of the input: 'year', 'month', 'day', 'hour', 'minute', or 'second'.
   * Returns null on failure.
   */
  public static function parseDate($str) {
    $str = trim($str);
    // american (middle endian)
    if (preg_match('$(\d{1,2})\s*/\s*(\d{1,2})(?:\s*/\s*(\d{1,4}))?$', $str, $matches) === 1) {
      if (isset($matches[3]))
        $year = intval($matches[3]);
      else
        $year = idate('Y');
      return array(
        $year, intval($matches[1]), intval($matches[2]), 0, 0, 0, 'day'
      );
    }
    // big endian
    if (preg_match('$(\d{4})(?:\s*[-/]\s*(\d{1,2})(?:\s*[-/]\s*(\d{1,2}))?)?$', $str, $matches) === 1) {
      $precision = 'year';
      $month = 1;
      $day = 1;
      if (isset($matches[2])) {
        $precision = 'month';
        $month = intval($matches[2]);
        if (isset($matches[3])) {
          $precision = 'day';
          $day = intval($matches[3]);
        }
      }
      return array(
        intval($matches[1]), $month, $day, 0, 0, 0, $precision
      );
    }
    return null;
  }
}
