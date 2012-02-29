<?php
/*
 * Template for blog post listing
 */

// Render the header
$this->renderTemplate('header.html');

$post = Post::getById(8);
$post->addToCache();
$post->title = 'From cache';
?>


<?php foreach ($posts as $post): ?>

<h2>
  <a href="<?php echo $post->link; ?>">
    <?php echo $post->title; ?>
  </a>
</h2>

<p>
  Published <?php echo $post->formatDate(); ?>
  @ <?php echo $post->formatTime(); ?>
</p>

<?php echo $post->content; ?>

<?php endforeach; ?>



<?php
// Render the footer
$this->renderTemplate('footer.html');
?>