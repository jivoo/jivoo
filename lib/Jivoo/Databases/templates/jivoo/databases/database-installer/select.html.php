<?php $this->layout('setup/layout.html'); ?>

<p><?php echo tr('Please select your desired database driver from the list below.'); ?></p>

<table>
<tbody>
<?php
foreach ($drivers as $driver) :
?>
<tr>
<td><?php echo $driver['name']; ?></td>
<?php if ($driver['isAvailable']) : ?>
<td>
<?php echo tr('Available'); ?>
</td>
<td>
<?php echo $Form->submit(
  tr('Select %1', $driver['name']),
  array('name' => $driver['driver'])
); ?>
</td>
<?php else : ?>
<td colspan="2">
<?php
echo tn(
  'Unavailable. Missing the "%1{", "}{" and "}" PHP extensions',
  'Unavailable. Missing the "%1{", "}{" and "}" PHP extension',
  $driver['missingExtensions']
);
?>
</td>
<?php endif; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>

