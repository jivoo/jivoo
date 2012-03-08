<?php
/**
 * PeanutCMS initialization
 *
 * @package PeanutCMS
 * @since 0.2.0
 */

// To hell with those "magic quotes"!
ini_set('magic_quotes_runtime', 0);

session_start();

/** We are now in PeanutCMS */
define('PEANUTCMS', TRUE);

/** PeanutCMS version string */
if (!defined('PEANUT_VERSION')) {
  define('PEANUT_VERSION', '0.2.0');
}

if (!defined('DEBUG')) {
  define('DEBUG', FALSE);
}

/** The absolute path of this installation */
if (!defined('PATH')) {
  define('PATH', str_replace('\\', '/', dirname(dirname(__FILE__))) . '/');
}

/** The path of this installation relative to website root */
if (!defined('WEBPATH')) {
  define('WEBPATH', str_replace(
    rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/'), '', PATH
  ));
}

/** Prefix for session-variables initialized by PeanutCMS */
if (!defined('SESSION_PREFIX')) {
  define('SESSION_PREFIX', 'peanut_');
}

/** URL of current PeanutCMS installation */
if (!defined('PEANUT_URL')) {
  define('PEANUT_URL', 'http://' . $_SERVER['SERVER_NAME'] . WEBPATH);
}

/** Default language */
if (!defined('LANGUAGE')) {
  define('LANGUAGE', 'en');
}

/** Directory which contains the PeanutCMS application */
if (!defined('APP')) {
  define('APP', 'app/');
}

if (!defined('CLASSES')) {
  define('CLASSES', 'classes/');
}

if (!defined('HELPERS')) {
  define('HELPERS', 'helpers/');
}

if (!defined('MODULES')) {
  define('MODULES', 'modules/');
}

if (!defined('PUB')) {
  define('PUB', 'public/');
}

if (!defined('PHP_VERSION_ID')) {
  $version = explode('.', PHP_VERSION);

  /** PHP version */
  define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}

if (PHP_VERSION_ID < 50200) {
  echo 'Sorry, but PeanutCMS does not support PHP versions below 5.2.0. You are currently using version ' . PHP_VERSION .'.';
  echo 'You should contact your webhost.'; 
  exit;
}

if (class_exists('DateTimeZone') AND !defined('DATETIMEZONE_AVAILABLE')) {
  /** The PHP5-class DateTimeZone is available */
  define('DATETIMEZONE_AVAILABLE', true);
}

/**
 * System timezone offset from UTC in seconds, used when DateTimeZone class is not available
 */
if (!defined('TIMEZONE_OFFSET') AND !defined('DATETIMEZONE_AVAILABLE')) {
  $hour = date('H', 0);
  $minute = date('i', 0);
  $second = date('s', 0);
  if (date('Y', 0) == '1969') {
    $hour -= 24;
    if ($minute > 0) {
      $minute = 60 - $minute;
      $hour += 1;
    }
    if ($second > 0) {
      $second = 60 - $second;
      $minute += 1;
    }
    $offset = $hour*60*60 - $minute*60 - $second;
  }
  else {
    $offset = $hour*60*60 + $minute*60 + $second;
  }
  define('TIMEZONE_OFFSET', $offset);
}

// Classes that has to be initialized, the order matters
$modules = array('errors', 'hooks', 'functions', 'filters', 'i18n',
    'configuration', 'http', 'actions', 'routes', 'templates',
    'theme', 'user', 'backend', 'posts', 'pages', 'render');

// Initialize PeanutCMS-array
// $PEANUT = array();

// if (!file_exists(PATH . INC . 'helpers/core-helpers.php'))
//   exit(PATH . INC . 'helpers/core-helpers.php was not found.');

/** Useful functions and aliases that are not part of the PEANUT-array */
require_once(PATH . APP . HELPERS . 'core-helpers.php');

require_once(PATH . APP . CLASSES . 'core.class.php');

$core = new Core();

foreach ($modules as $module) {
  $core->loadModule($module);
}

