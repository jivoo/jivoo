<?php echo $Form->form(); ?>

<div class="field">
<?php echo $Form->label('username', tr('Username')); ?>
<?php echo $Form->text('username'); ?>
</div>

<div class="field">
<?php echo $Form->label('password', tr('Password')); ?>
<?php echo $Form->password('password'); ?>
</div>

<?php echo $Form->submit(tr('Log in')); ?>

<?php echo $Form->end(); ?>