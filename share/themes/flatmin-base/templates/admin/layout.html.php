<?php
$this->meta('viewport', 'width=device-width, initial-scale=1');
$this->import(
  'jivoo/jtk/notifications.js',
  'admin/icomoon/style.css',
  'admin/theme.css',
  'jquery.js',
  'jquery.amaran.js',
  'admin/theme.js',
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
<li><?php echo $Icon->link(h($user->username), 'Admin', 'user'); ?></li>
<li><?php echo $Icon->link('Log out', 'Admin::logout', 'exit'); ?></li>
<li class="account-menu notifications">
  <?php echo $Icon->link('3', array('url' => '#'), 'key'); ?>
<ul>
<li><a href="#"><span class="icon icon-bell"></span><span class="label">Notifications</span><span class="count">3</span></a></li>
<li><?php echo $Icon->link(h($user->username), 'Admin', 'user'); ?></li>
<li><?php echo $Icon->link('Log out', 'Admin::logout', 'exit'); ?></li>
</ul>
</li>
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
