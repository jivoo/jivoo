<?php echo $post->content; ?>

<?php if ($Auth->isLoggedIn()): ?>
<?php echo $Form->form(array(
  'action' => 'delete',
  $post->id
), array('method' => 'delete')); ?>
<p>
<?php echo $Html->link(tr('Edit'), array(
  'action' => 'edit',
  $post->id
), array('class' => 'button')); ?>

<?php echo $Form->submit(tr('Delete')); ?>
</p>
<?php echo $Form->end(); ?>
<?php endif; ?>

<h2><?php echo tr('Comments'); ?></h2>

<?php echo $Snippet('Comments\Index', $post->id); ?>

<h2><?php echo tr('Leave a comment'); ?></h2>

<?php echo $Snippet('Comments\Add', $post->id); ?>