<?php
require_once '../../src/bootstrap.php';

$app = new Jivoo\Core\App(
  'app',
  'user',
  basename(__FILE__)
);

$app->paths->share = '../../share';

$app->run('development');
