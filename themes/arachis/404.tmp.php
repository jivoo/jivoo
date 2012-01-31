<?php
/* 
 * Template for "404 not found"
 */

// Render the header
$this->renderTemplate('header');
?>

<h1><?php echo tr('Page not found'); ?></h1>

<p><?php echo tr('The page you were looking for could not found.'); ?></p>

<?php
// Render the footer
$this->renderTemplate('footer');
?>