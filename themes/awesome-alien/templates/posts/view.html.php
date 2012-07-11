<?php
/* 
 * Template for blog post
 */

// Render the header
$this->render('header');
?>

<div class="post">

<h1><?php echo h($post->title); ?></h1>

<?php echo $post->content; ?>

<?php
$tags = array();
foreach ($post->getTags() as $tag) {
  $tags[] = $Html->link(h($tag->tag), $tag);
}
?>
<div class="byline">
<?php
if (count($tags) > 0) {
  echo trl('Posted on %1 and tagged with %l', 'Posted on %1 and tagged with %l',
    ', ', ' and ', $tags, $post->formatDate());
}
else {
  echo tr('Posted on %1', $post->formatDate());
}
?>
 | <a href="<?php echo $this->link($post); ?>#comment"><?php echo tr('Leave a comment'); ?></a></div>

</div>

<?php
if ($post->countComments() > 0):
?>
<h1 id="comments"><?php echo trn('%1 comment', '%1 comments', $post->comments); ?></h1>

<ul class="comments">
<?php
$level = 0;
foreach ($comments as $comment):
  if (isset($comment->level)) {
    if ($level == $comment->level) {
      echo '</li>';
    }
    else if ($level > $comment->level) {
      for ($i = $comment['level']; $level > $i; $i++)
        echo '</li></ul>';
      echo '</li>';
    }
    if ($level >= 0 AND $level < $comment->level)
      echo '<ul>';
  }
?>
  
<li>
<div class="comment-avatar">
<img src="http://1.gravatar.com/avatar/<?php
if (isset($comment->email))
  echo md5($comment->email);
else
  echo md5($comment->ip);
?>?s=40&amp;d=monsterid&amp;r=G"
     alt="<?php echo $comment->author; ?>"/>
</div>
<div class="comment" id="comment<?php echo $comment->id; ?>">
<h2><?php
if (empty($comment->author)) {
  echo tr('Anonymous');
}
else {
  if (empty($comment->website))
    echo $comment->author;
  else
    echo '<a href="' . $comment->website . '">' . $comment->author . '</a>';
}
?></h2>
<p><?php echo $comment->content; ?></p>
<div class="byline">
<?php
echo tr('%1 at %2', $comment->formatDate(), $comment->formatTime());
  echo ' | <a href="#">' . tr('Reply') . '</a>';
?>
</div>
</div>
<div class="clear"></div>

<?php
  if (isset($comment->level))
    $level = $comment->level;
  else
    echo '</li>';
endforeach;
for ($i = $level; $i >= 0; $i--)
  echo '</li></ul>';
?>

<div class="pagination">
  <?php if (!$Pagination->isFirst()) echo $Html->link('&#8592; Back ', $Pagination->prevLink('comments')); ?>
  <div class="right">
    <?php if (!$Pagination->isLast()) echo $Html->link('More comments &#8594;', $Pagination->nextLink('comments')); ?>
  </div>
</div>

<?php
endif;
?>

<?php if ($post->commenting == 'yes'): ?>

<h1 id="comment"><?php echo tr('Leave a comment'); ?></h1>

<p><?php echo tr('Have something to say? Say it!'); ?>

<?php echo $Form->begin($newComment, 'comment'); ?>

<p class="input">
<?php echo $Form->label('author'); ?>
<?php echo $Form->isRequired('author', '<span class="star">*</span>'); ?>
<?php echo $Form->field('author'); ?>
<?php echo $Form->getError('author'); ?>
</p>

<p class="input">
<?php echo $Form->label('email'); ?>
<?php echo $Form->isRequired('email', '<span class="star">*</span>'); ?>
<?php echo $Form->field('email'); ?>
<?php echo $Form->getError('email'); ?>
</p>

<p class="input">
<?php echo $Form->label('website'); ?>
<?php echo $Form->isRequired('website', '<span class="star">*</span>'); ?>
<?php echo $Form->field('website'); ?>
<?php echo $Form->getError('website'); ?>
</p>

<p class="input">
<?php echo $Form->label('content'); ?>
<?php echo $Form->isRequired('content', '<span class="star">*</span>'); ?>
<?php echo $Form->field('content'); ?>
<?php echo $Form->getError('content'); ?>
</p>

<p><?php echo $Form->submit(tr('Post comment')); ?></p>

<?php echo $Form->end(); ?>

<?php endif; ?>

<?php
// Render the footer
$this->render('footer');
?>
