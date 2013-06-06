<?php
/**
 * Useful functions
 * 
 * @package ApakohPHP
 */
class Utilities {
 
  private function __construct() {
  }

  /**
   * Convert a CamelCase class-name to a lowercase dash-separated name. E.g.
   * from "CamelCase" to "camel-case".
   * @param string $camelCase A camel case string
   * @return string Dash-separated string
   */
  public static function camelCaseToDashes($camelCase) {
    $dashes = preg_replace('/([A-Z])/', '-$1', lcfirst($camelCase));
    return strtolower($dashes);
  }

  /**
   * Convert a lowercase dash-separated name to a camel case class-name. E.g.
   * from "camel-case" to "CamelCase".
   * @param string $dashes  Dash-separated string
   * @return string A camel case string
   */
  public static function dashesToCamelCase($dashes) {
    $words = explode('-', $dashes);
    $camelCase = '';
    foreach ($words as $word) {
      $camelCase .= ucfirst($word);
    }
    return $camelCase;
  }
  
  /**
   * Get plural form of word
   * @param string $word Word
   * @return string Plural
   */
  public static function getPlural($word) {
    return $word . 's';
  } 
  
  public static function isUpper($char) {
    $ascii = ord($char);
    return $ascii >= 65 AND $ascii <= 90;
  }
  
  public static function isLower($char) {
    $ascii = ord($char);
    return $ascii >= 97 AND $ascii <= 122;
  }

  /**
   * Returns the portion of string specified by the start and length parameters.
   * @param string $string
   * @param int $start
   * @param int $length
   * @return string Returns the extracted part of string; or FALSE on failure, or an empty string.
   */
  public static function substr($string, $start, $length = NULL) {
    if (function_exists('mb_substr')) {
      return mb_substr($string, $start, $length, 'UTF-8');
    }
    else {
      return join('', array_slice(
        preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY), $start, $length
      ));
    }
  }
  
  /**
   * Test a condition and throw an exception if it's false 
   * @param boolean $condition Condition
   * @throws InvalidArgumentException When condition is false
   */
  public static function precondition($condition) {
    if ($condition === true) {
      return;
    }
    $bt = debug_backtrace();
    $call = $bt[0];
    $lines = file($call['file']);
    preg_match('/' . $call['function'] . '\((.+)\)/',
      $lines[$call['line'] - 1], $matches);
    throw new InvalidArgumentException(
      'Precondition not met (' . $matches[1] . ').');
  }

  public static function getContentType($fileName) {
    $fileExt = strtolower($fileName);
    if (strpos($fileExt, '.')) {
      $segments = explode('.', $fileExt);
      $fileExt = $segments[count($segments) - 1];
    }
    switch ($fileExt) {
      case 'htm':
        $fileExt = 'html';
      case 'css':
      case 'js':
      case 'html':
        return "text/" . $fileExt;
      case 'json':
        return "application/json";
      case 'rss':
        return "application/rss+xml";
      case 'xml':
        return "application/xml";
      case 'jpg':
        $fileExt = 'jpeg';
      case 'gif':
      case 'jpeg':
      case 'png':
        return "image/" . $fileExt;
      default:
        return "text/plain";
    }
  }

  public static function groupObjects(&$objects) {
    if (!is_array($objects) OR count($objects) < 1) {
      return false;
    }
    uasort($objects, array('Utilities', 'groupSorter'));
  }

  public static function groupSorter(IGroupable $a, IGroupable $b) {
    $groupA = $a->getGroup();
    $groupB = $b->getGroup();
    if (is_numeric($groupA) AND is_numeric($groupB)) {
      return $groupA - $groupB;
    }
    else {
      return strcmp($groupA, $groupB);
    }
  }
  
  /**
   * Comparison function for use with usort() and uasort()
   *
   * @param array $a
   * @param array $b
   */
  public static function prioritySorter($a, $b) {
    return $b['priority'] - $a['priority'];
  }
}
