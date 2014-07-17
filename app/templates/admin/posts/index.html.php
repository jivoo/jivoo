<?php $this->extend('admin/layout.html'); ?>

<?php
$widget = $Widget->begin('DataTable', array(
  'model' => $posts,
  'columns' => array('title', 'author', 'status', 'updatedAt'),
  'labels' => array(
    'author' => tr('Author'),
  ),
  'sortOptions' => array('title', 'status', 'updatedAt', 'createdAt'),
  'defaultSortBy' => 'updatedAt',
  'defaultDescending' => true,
  'filters' => array(
    tr('Published') => 'status=published',
    tr('Pending review') => 'status=pending',
    tr('Draft') => 'status=draft'
  ),
  'actions' => array(
    new RowAction(tr('Edit'), 'edit', 'pencil'),
    new RowAction(tr('View'), 'view', 'screen'),
    'publish' => new RowAction(tr('Publish'), 'publish', 'eye'),
    'unpublish' => new RowAction(tr('Unpublish'), 'unpublish', 'eye-blocked'),
    new RowAction(tr('Delete'), 'delete', 'remove'),
  ),
  'bulkActions' => array(
    new BulkAction(tr('Edit'), 'Admin::Posts::bulkEdit', 'pencil'),
    new BulkAction(tr('Publish'), 'Admin::Posts::bulkEdit', 'eye'),
    new BulkAction(tr('Unpublish'), 'Admin::Posts::bulkEdit', 'eye-blocked'),
    new BulkAction(tr('Delete'), 'Admin::Posts::bulkEdit', 'remove'),
  )
));
foreach ($widget as $item) {
  echo $widget->handle($item, array(
    'id' => $item->id,
    'cells' => array(
      $Html->link($item->title, $item->action('edit')),
      $Html->link($item->user->username, $item->user),
      $item->status,
      ldate($item->updatedAt)
    ),
    'removeActions' => array($item->status == 'published' ? 'publish' : 'unpublish')
  ));
}
echo $widget->end();
?>