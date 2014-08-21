<tr<?php if (isset($class)) echo ' class="' . $class . '"'; ?>>
<td class="selection">
<label>
<input type="checkbox" name="selection" value="<?php echo $options['id']; ?>" />
</label>
</td>
<?php $i = 0; ?>
<?php foreach ($options['columns'] as $column): ?>
<?php if ($column == $options['primaryColumn']): ?>
<td class="primary">
<?php echo $options['cells'][$i]; ?>
<div class="essential">
<?php $j = 0; ?>
<?php foreach ($options['columns'] as $column): ?>
<?php if ($column != $options['primaryColumn']): ?>
<span><?php echo $options['labels'][$column]; ?>:
<?php echo $options['cells'][$j]; ?></span>
<?php endif; ?>
<?php $j++; ?>
<?php endforeach; ?>
</div>
<div class="action-links">
<?php foreach ($options['actions'] as $action): ?>
<?php echo $Html->link(h($action->label), $options['record']->action($action->action)); ?>
<?php endforeach; ?>
</div>
</td>
<?php else: ?>
<td>
<?php echo $options['cells'][$i]; ?>
</td>
<?php endif; ?>
<?php $i++; ?>
<?php endforeach; ?>
<td class="actions">
<?php foreach ($options['actions'] as $action): ?>
<?php echo $Html->link(
  $Icon->icon($action->icon),
  $this->link($this->mergeRoutes($action->route, array($options['id']))),
  array(
    'title' => $action->label,
    'data' => array(
      'method' => $action->method,
      'data' => json_encode($action->data),
      'confirm' => $action->confirmation
    )
  )
); ?>
<?php endforeach; ?>
</td>
</tr>