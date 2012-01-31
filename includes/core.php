<?php
/**
 * PeanutCMS core script
 *
 * @package PeanutCMS
 * @version 0.1.0
 */

// To hell with those "magic quotes"!
ini_set('magic_quotes_runtime', 0);

session_start();

/** We are now in PeanutCMS */
define('PEANUTCMS', TRUE);

/** PeanutCMS version string */
if (!defined('PEANUT_VERSION'))
  define('PEANUT_VERSION', '0.1.0');

/** The absolute path of this installation */
if (!defined('PATH'))
  define('PATH', str_replace('\\', '/', dirname(dirname(__FILE__))) . '/');

/** The path of this installation relative to website root */
if (!defined('WEBPATH'))
  define('WEBPATH', str_replace(rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/'), '', PATH));

/** Prefix for session-variables initialized by PeanutCMS */
if (!defined('SESSION_PREFIX'))
  define('SESSION_PREFIX', 'peanut_');

/** URL of current PeanutCMS installation */
if (!defined('PEANUT_URL'))
  define('PEANUT_URL', 'http://' . $_SERVER['SERVER_NAME'] . WEBPATH);

/** Default language */
if (!defined('LANGUAGE'))
  define('LANGUAGE', 'en');

/** Directory which contains the PeanutCMS files */
if (!defined('INC'))
  define('INC', 'includes/');

/** Data directory */
if (!defined('DATA'))
  define('DATA', 'data/');

/** Language directory */
if (!defined('LANG'))
  define('LANG', 'languages/');

/** Themes directory */
if (!defined('THEMES'))
  define('THEMES', 'themes/');

if (!defined('PHP_VERSION_ID')) {
  $version = explode('.', PHP_VERSION);

  /** PHP version */
  define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
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
$classes = array('errors', 'hooks', 'functions', 'filters', 'i18n',
    'configuration', 'flatfiles', 'http', 'actions', 'templates',
    'theme', 'user', 'backend', 'posts', 'pages', 'render');

// Initialize PeanutCMS-array
$PEANUT = array();

if (!file_exists(PATH . INC . 'functions.php'))
  exit(PATH . INC . 'functions.php was not found.');

/** Useful functions and aliases that are not part of the PEANUT-array */
require_once(PATH . INC . 'functions.php');

foreach ($classes as $class) {
  $className = ucfirst($class);
  $classFile = $class . '.class.php';
  if (!file_exists(PATH . INC . $classFile)) {
    if (isset($PEANUT['errors'])) {
      $PEANUT['errors']->fatal(tr('Class missing'), tr('%1 was not found', PATH . INC . $classFile));
    }
    else {
      exit(tr('%1 was not found', PATH . INC . $classFile));
    }
  }
  require_once(PATH . INC . $classFile);
  $PEANUT[$class] = new $className();
  if (isset($PEANUT['hooks']))
    $PEANUT['hooks']->run($class . 'Ready');
}

