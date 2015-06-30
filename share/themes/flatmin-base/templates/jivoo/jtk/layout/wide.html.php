<?php
$this->meta('viewport', 'width=device-width, initial-scale=1');
$this->import(
  'jivoo/jtk/notifications.js',
  'icomoon/style.css',
  'jivoo/core.css',
  'jivoo/jtk/theme.css',
  'jquery.js',
  'jquery.amaran.js',
  'jivoo/jtk/theme.js',
  'html5shiv.js',
  'respond.js'
);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<title><?php echo $title . ' | ' . $app['name']; ?></title>

<?php echo $this->block('meta'); ?>

<?php echo $this->resourceBlock(); ?>

</head>
<body data-loadmsg="<?php echo tr('Loading&hellip;'); ?>">

<header>
<a href="#" class="toggle-menu"></a>
<h1><?php echo $app['name']; ?></h1>

<ul class="account">
<?php echo $this->block('account-menu'); ?>
</ul>

<?php echo $Jtk->Menu($shortcutsMenu); ?>

</header>

<nav>

<?php echo $Jtk->Menu($mainMenu); ?>

</nav>

<div id="main">

<div id="main-container">

<?php if (isset($title)): ?>
<h1><?php echo $title; ?></h1>
<?php endif; ?>

<?php echo $this->block('content'); ?>
</div>

</div>

<footer>
<?php if (isset($app['website'])): ?>
<?php echo $Html->link($app['name'] . ' ' . $app['version'], $app['website']); ?>
<?php else: ?>
<?php echo $app['name'] . ' ' . $app['version'];?>
<?php endif; ?>
</footer>

<script type="text/javascript">
$(function() {
<?php foreach ($flash as $message): ?>
  JIVOO.notifications.send(<?php echo Jivoo\Core\Json::encode($message->message); ?>, '<?php echo $message->type; ?>');
<?php endforeach; ?>
});
</script>

</body>
</html>
