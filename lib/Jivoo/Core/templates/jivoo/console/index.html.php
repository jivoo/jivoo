<p><?php echo tr('Welcome to the Jivoo web application framework.')?></p>

<p><?php echo tr(
  'No valid application configuration was found in the specified application directory (%1).',
  $appDir
); ?></p>

<p><?php echo tr('Do you want Jivoo to generate a new application?')?></p>

<p>
<?php echo $Html->link(tr('Continue'), 'snippet:Jivoo\Console\Configure', array('class' => 'button')); ?>
</p>