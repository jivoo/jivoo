<?php
$this->import('blog.css');
$this->meta('viewport', 'width=device-width, initial-scale=1');
?>
<!DOCTYPE html>
<html>
<head>
<title><?php
if (isset($title))
  echo h($title) . ' | ';
echo h($blogTitle);
?></title>

<?php echo $this->block('meta'); ?>

<?php echo $this->resourceBlock(); ?>

</head>
<body>

<div id="main">
<header>
<?php echo $Html->link(h($blogTitle), null, array('class' => 'title')); ?>

<nav>
<ul>
<li><?php echo $Html->link(tr('Home'), null); ?></li>
<?php if ($Auth->isLoggedIn()): ?>
<li><?php echo $Html->link(tr('Add post'), 'Posts::add'); ?></li>
<li><?php echo $Html->link(tr('Settings'), 'App::settings'); ?></li>
<li><?php echo $Html->link(tr('Log out'), 'App::logout'); ?></li>
<?php else: ?>
<li><?php echo $Html->link(tr('Admin'), 'App::login'); ?></li>
<?php endif; ?>
</ul>
</nav>
</header>

<div id="primary">
<div id="content">
<?php if (isset($title)): ?>
<h1><?php echo h($title); ?></h1>
<?php endif; ?>

<?php foreach ($Notify as $message): ?>
<p class="flash flash-<?php echo $message->type; ?>"><?php echo $message; ?></p>
<?php endforeach; ?>

<?php echo $this->block('content'); ?>
</div>

<aside>

<div class="widget">
<div class="widget-title"><?php echo tr('Recent Posts'); ?></div>
<div class="widget-content">
<?php echo $Snippet('Sidebar\RecentPosts'); ?>
</div>
</div>

<div class="widget">
<div class="widget-title"><?php echo tr('Recent Comments'); ?></div>
<div class="widget-content">
<?php echo $Snippet('Sidebar\RecentComments'); ?>
</div>
</div>

</aside>
<footer>
</footer>
</div>

</div>

</body>
</html>
