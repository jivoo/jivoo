<ul>
<?php foreach ($menu as $item): ?>
<?php
$current = $this->isCurrent($item->route);
?>
<li>
<?php
$url = $this->link($item->route);
if ($url != ''):
?>
<a href="<?php echo h($url); ?>"<?php
if ($current) echo ' class="current"'; ?>>
<?php else: ?>
<a>
<?php endif; ?>
<?php if (isset($item->icon)): ?>
<span class="icon"><?php echo $Icon->icon($item->icon); ?></span><?php endif; ?>
<span class="label"><?php echo $item->label; ?></span>
<?php if (isset($item->badge)): ?>
<span class="count"><?php echo $item->badge; ?></span>
<?php endif; ?>
</a>
<?php if ($item instanceof Jivoo\Jtk\Menu\Menu): ?>
<?php $m = $Jtk->IconMenu->menu($item); echo $m(); ?>
<?php endif; ?>
</li>
<?php endforeach; ?>
</ul>