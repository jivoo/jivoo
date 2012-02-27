<?php
/*
 * Template for blog post listing
 */


$posts = Post::select(
  Selector::create()
    //->where('state', 'unpublished')
    ->orderBy('date')
    ->desc()
    ->limit(3)
    ->offset(0)
);

$posts[0]->comments = 2;

// Render the header
$this->renderTemplate('header.html');

?>



<p>Blog listing</p>

<?php foreach ($posts as $post): ?>

<h2><a href="<?php echo $post->link; ?>"><?php echo $post->title; ?></a></h2>

<p>Published <?php echo $post->formatDate(); ?> @
<?php echo $post->formatTime(); ?>
</p>

<?php echo $post->content; ?>

<?php endforeach; ?>



<?php
// Render the footer
$this->renderTemplate('footer.html');
?>