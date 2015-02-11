<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<title><?php echo $title; ?></title>

<meta name="generator" content="Jivoo" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<style type="text/css">
<?php include dirname(__FILE__) . '/../../assets/css/core.css'; ?>
</style>
</head>
<body class="exception">

<header>
<h1><?php echo $app; ?></h1>
</header>

<div id="main">
<div id="sad">
:-(
</div>
<h1><?php echo $title; ?></h1>

<?php
if (isset($exception)) {
  $file = $exception->getFile();
  $line = $exception->getLine();
  $message = $exception->getMessage();
?>
<h2><?php echo $message ?></h2>

<p><?php echo tr(
  'An uncaught %1 was thrown in file %2 on line %3 that prevented further execution of this request.',
  '<strong>' . get_class($exception) . '</strong>',
  '<em>' . basename($file) . '</em>', '<strong>' . $line . '</strong>'
); ?></p>
<p><?php echo tr('The exception was thrown from the following file:')?></p>
<p><code><?php echo $file ?></code></p>
<h2><?php echo tr('Stack Trace') ?></h2>
<table class="trace">
<thead>
<tr>
<th><?php echo tr('File') ?></th>
<th><?php echo tr('Class') ?></th>
<th><?php echo tr('Function') ?></th>
<th><?php echo tr('Arguments') ?></th>
</tr>
</thead>
<tbody>
<?php foreach ($exception->getTrace() as $i => $trace): ?>
<tr class="<?php echo (($i % 2 == 0) ? 'even' : 'odd') ?>">
<td>
<span title="<?php echo (isset($trace['file']) ? $trace['file'] : '') ?>">
<?php echo (isset($trace['file']) ? basename($trace['file']) : '') ?>
<?php echo (isset($trace['line']) ? ' : ' . $trace['line'] : '') ?>
</span></td>
<td><?php echo (isset($trace['class']) ? $trace['class'] : '') ?></td>
<td><?php echo (isset($trace['function']) ? $trace['function'] : '') ?></td>
<td>
<?php if (isset($trace['args'])): ?>
<?php foreach ($trace['args'] as $j => $arg): ?>
<span title="<?php
if (is_scalar($arg)) {
  echo h($arg);
}
else if (is_object($arg)) {
  echo get_class($arg);
}
else if (is_array($arg)) {
  echo count($arg);
}
?>"><?php echo gettype($arg) ?>
</span><?php echo ($j < count($trace['args']) - 1 ? ', ' : '') ?>
<?php endforeach; ?>
<?php else: ?>
null
<?php endif; ?>
</td></tr>
<?php endforeach; ?>
</tbody>
</table>

<h2><?php echo tr('System'); ?></h2>

<table class="trace">
<tbody>
<tr>
<td><?php echo tr('Operating system'); ?></td>
<td><?php echo php_uname(); ?></td>
</tr>
<tr>
<td><?php echo tr('PHP version'); ?></td>
<td><?php echo phpversion(); ?></td>
</tr>
<tr>
<td><?php echo tr('Server API'); ?></td>
<td><?php echo php_sapi_name(); ?></td>
</tr>
<tr>
<td><?php echo tr('%1 version', 'Jivoo'); ?></td>
<td><?php echo Jivoo\Core\VERSION; ?></td>
<tr>
<td><?php echo tr('%1 version', $app); ?></td>
<td><?php echo $version; ?></td>
</tr>
</tbody>
</table>

<h2><?php echo tr('Log messages')?></h2>

<?php
$log = Jivoo\Core\Logger::getLog()
?>

<table class="trace">
<thead>
<tr>
<th style="width: 50%;"><?php echo tr('Message') ?></th>
<th><?php echo tr('File') ?></th>
<th><?php echo tr('Time') ?></th>
</tr>
</thead>
<tbody>
<?php foreach ($log as $message): ?>
<tr>
<td>
[<?php echo Jivoo\Core\Logger::getType($message['type']); ?>]
<?php echo h($message['message']); ?>
</td>
<td>
<span title="<?php echo (isset($message['file']) ? $message['file'] : '') ?>">
<?php echo (isset($message['file']) ? basename($message['file']) : '') ?>
<?php echo (isset($message['line']) ? ' : ' . $message['line'] : '') ?>
</span>
</td>
<td><?php echo date('Y-m-d H:i:s', $message['time']); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php
}
else {
  echo $body;
}
?>

</div>

<footer>
<?php echo $app; ?> 
<?php echo $version; ?>
</footer>


</body>
</html>