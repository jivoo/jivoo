<?php $this->extend('feed/layout.rss'); ?>

<?php foreach ($posts as $post): ?>

  <item>
    <title><?php echo $post->title; ?></title>
    <description><![CDATA[<?php echo $Format->html($post, 'content'); ?>]]></description>
    <link><?php echo $this->url($post); ?></link>
    <pubDate><?php echo date('r', $post->published); ?></pubDate>
    <guid><?php echo $this->url($post); ?></guid>
  </item>

<?php 
if (!isset($this->lastBuildDate)) {
  $this->lastBuildDate = $post->published;
}
?>

<?php endforeach; ?>
