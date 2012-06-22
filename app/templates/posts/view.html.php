<?php
/*
 * Template for blog post
 */

// Render the header
$this->render('header');
?>

<h2><?php echo h($post->title); ?></h2>

<p>Published <?php echo $post->formatDate(); ?>
  - <?php echo $post->formatTime(); ?>
  <?php if ($author = $post->getUser()): ?>
  by <?php echo $author->username; ?>
  <?php endif; ?>
  <a href="#comment">
    (<?php echo tr('Leave a comment'); ?>)
  </a>
</p>

<?php echo $post->content; ?>

<?php $tags = $post->getTags(); ?>

<?php if (count($tags) > 0): ?>
<h3>Tags</h3>
<?php
foreach ($tags as $tag) {
  echo $Html->link(h($tag->tag), $tag) . ' ';
}
endif;
?>

<h3>Comments</h3>

<?php
foreach ($post->getComments() as $comment):
?>

<div style="border-left:1px solid #000; padding-left:10px; margin-left: 20px">

<p>
<?php echo $Html->link('#', $comment); ?>
Published by <?php
if ($comment->website == '')
  echo $comment->author;
else
  echo '<a href="' . $comment->website . '">' . $comment->author . '</a>';
?> on <?php echo $comment->formatDate(); ?> -
<?php echo $comment->formatTime(); ?>
</p>

<?php echo $comment->content; ?>
</div>

<?php
endforeach;
?>

<?php
// Render the footer
$this->render('footer');
?>
