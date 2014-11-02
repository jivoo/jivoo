<?php $this->extend('admin/layout.html'); ?>

<?php
$widget = $Widget->begin('BasicDataTable', array(
  'model' => $model,
  'records' => $extensions,
  'columns' => array('name', 'enabled', 'canonicalName', 'version'),
  'primaryColumn' => 'name',
  'sortOptions' => array('name', 'enabled', 'canonicalName'),
  'defaultSortBy' => 'name',
  'actions' => array(
    'enable' => new TableAction(tr('Enable'), 'Admin::Extensions::enable',
      'checkmark', array(), 'post'),
    'disable' => new TableAction(tr('Disable'), 'Admin::Extensions::disable',
      'close', array(), 'post'),
  ),
));
foreach ($widget as $item) {
  echo $widget->handle($item, array(
    'id' => $item->canonicalName,
    'removeActions' => array($item->enabled ? 'enable' : 'disable')
  ));
}
echo $widget->end();
?>