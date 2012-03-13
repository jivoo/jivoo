<?php

class Errors implements IModule {

  public function __construct() {
    set_exception_handler(array($this, 'handleException'));
    // regular error handler should be implemented aswell
  }

  public static function getDependencies() {
    return array();
  }

  public function handleException(Exception $exception) {
    if (!DEBUG) {
      self::fatal(tr('Fatal error'), tr('An uncaught exception was thrown.'));
    }
    $file = $exception->getFile();
    $line = $exception->getLine();
    $message = $exception->getMessage();
    /* This should (!!) be a template/view instead..
     * Or should it? (What if the template is missing?) */
    $body = '<h2>' . $message . '</h2>';

    $body .= '<p>'
    . tr('An uncaught %1 was thrown in file %2 on line %3 that prevented further execution of this request.',
                  '<strong>' . get_class($exception) . '</strong>',
                  '<em>' . basename($file) . '</em>', '<strong>' . $line . '</strong>')
    . '</p><h2>'
    . tr('Where it happened')
    . '</h2><p><code>'
    . $file
    . '</code></p><h2>'
    . tr('Stack Trace')
    . '</h2><table class="trace"><thead><tr><th>'
    . tr('File')
    . '</th><th>'
    . tr('Line')
    . '</th><th>'
    . tr('Class')
    . '</th><th>'
    . tr('Function')
    . '</th><th>'
    . tr('Arguments')
    . '</th></tr></thead><tbody>';

    foreach ( $exception->getTrace() as $i => $trace ) {
      $body .= '<tr class="' . (($i % 2 == 0) ? 'even' : 'odd') . '">'
      . '<td>' . (isset($trace['file']) ? basename($trace['file']) : '') .'</td>'
      . '<td>' . (isset($trace['line']) ? $trace['line'] : '') .'</td>'
      . '<td>' . (isset($trace['class']) ? $trace['class'] : '') .'</td>'
      . '<td>' . (isset($trace['function']) ? $trace['function'] : '') .'</td>'
      . '<td>';
      if (isset($trace['args'])) {
        foreach($trace['args'] as $j => $arg) {
          $body .= ' <span title="' . var_export($arg, true) . '">' . gettype($arg) . '</span>'
          . ($j < count($trace['args']) -1 ? ',' : '');
        }
      }
      else {
        $body .= 'NULL';
      }
      $body .= '</td></tr>';
    }
    $body .= '</tbody></table>';
    self::exceptionLayout(tr('Uncaught exception'), $body);
  }

  /**
  * Outputs an error page and kills PeanutCMS
  *
  * @param string $title Title of error
  * @param string $message Short message explaining error
  * @param string $more A longer HTML-formatted (should use paragraphs <p></p>) explanation of the error
  * @return void
  */
  public static function fatal($title, $message, $more = NULL) {
    $body = '<h2>' . $message . '</h2>';

    if (!isset($more)) {
      $body .= '<p>'
      . tr('A fatal error has prevented further execution of this request.')
      . '</p>';
    }
    else {
      $body .= $more;
    }
    $body .= '<h2>' . tr('What now?') . '</h2>';

    $body .= '<p>'
    . tr('As a <strong>user</strong> you should contact the owner of this website and notify them of this error.')
    . '</p><p>'
    . tr('As a <strong>webmaster</strong> you should contact the developers of PeanutCMS and notify them of this error.')
    . '</p><p>'
    . tr('As a <strong>developer</strong> you should turn on debugging to get more information about this error.')
    . '</p>';

    self::exceptionLayout($title, $body);
  }

  private static function exceptionLayout($title, $body) {
    ob_start();
    echo '<!DOCTYPE html>
      <html>
        <head>
          <title>' . $title . '</title>

          <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
          <link rel="stylesheet" href="' . WEBPATH . PUB . 'css/backend.css" type="text/css" />
          <link rel="stylesheet" href="' . WEBPATH . PUB . 'css/exception.css" type="text/css" />

        </head>
        <body>

          <div id="header">
            <div id="bar">
              <div class="right">PeanutCMS</div>
            </div>
            <div id="shadow"></div>
          </div>

          <div id="content">
            <div class="section">
              <div class="container">
                <div id="sad">
           			:-(
                </div>
                <h1>' . $title . '</h1>

                <div class="clearl"></div>

                ' . $body . '

              </div>
            </div>
          </div>

          <div class="footer" id="poweredby">
            Powered by <a href="#">PeanutCMS 0.1</a>
          </div>

          <div class="footer" id="links">
            <a href="#">About</a>
          </div>

        </body>
      </html>';
    $output = ob_get_clean();
    $length = strlen($output);
    // When used as error page the page has to be at least 512 bytes long for Chrome and IE to care about it.
    if ($length < 513) {
      for ($i = 0; $i < (513-$length); $i++) {
        $output .= ' ';
      }
    }
    echo $output;
    exit;
  }
}