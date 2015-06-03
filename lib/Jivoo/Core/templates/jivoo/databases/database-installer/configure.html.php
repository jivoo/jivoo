<?php $this->layout('jivoo/setup/layout.html'); ?>

<?php echo $Form->formFor($form, null); ?>

<p><?php echo tr(
  'You have selected the %1 database driver.',
  '<strong>' . $driver['name'] . '</strong>');
?>
 <?php echo tr('The following information is required.'); ?>
</p>

<?php foreach ($form->getFields() as $field) : ?>
<div class="field<?php echo $Form->ifRequired($field, ' field-required'); ?>">
<?php echo $Form->label($field); ?>
<?php echo $Form->text($field); ?>
<?php if ($Form->isValid($field)) : ?> 
<?php
switch ($field) {
  case 'filename':
    echo tr('The location of the database.');
    break;
  case 'tablePrefix':
    echo tr(
      'Can be used to prevent conflict with other tables in the database.');
    break;
}
?>
<?php else : ?>
<?php echo $Form->error($field); ?>
<?php endif; ?>
</div>
<?php endforeach; ?>