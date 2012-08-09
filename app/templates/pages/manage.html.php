<?php
// Render the header
$this->render('backend/header.html');
?>


      <div class="section light_section">
        <div class="container pagination">
          <span class="older">
<?php if (!$Pagination->isLast()) echo $Html->link('&#8592; Older', $Pagination->nextLink()); ?>
          &nbsp;</span>
          <span class="pages">
            Page <?php echo $Pagination->getPage(); ?>
            of <?php echo $Pagination->getPages(); ?>
          </span>
          <form action="<?php echo $this->link(array()); ?>" method="get">
          <span class="filter">
            <input type="text" class="text" name="filter" value="<?php echo h($filter); ?>" />
          </span>
          </form>
          <span class="newer">&nbsp;
<?php if (!$Pagination->isFirst()) echo $Html->link('Newer &#8594;', $Pagination->prevLink()); ?>
          </span>
          <div class="clearl"></div>
        </div>
      </div>
      <div class="section light_section">
        <div class="container">
<?php if (count($pages) < 1): ?>
          <div class="center">
          <?php echo tr('No pages matched your search criteria.')?>
          </div>
<?php endif; ?>
<?php $first = TRUE; ?>
<?php foreach ($pages as $page): ?>

          <div class="record<?php if ($first) { echo ' first'; $first = FALSE; } ?>">
          <span class="title">
<?php echo $Html->link(h($page->title), array('action' => 'edit', 'parameters' => array($page->id))); ?>
          </span>
          <span class="state">
            <?php echo ucfirst($page->state); ?>
          </span>
          <span class="actions">
<?php echo $Html->link('Edit', array('action' => 'edit', 'parameters' => array($page->id))); ?>

<?php echo $Html->link('View', array('action' => 'view', 'parameters' => array($page->id))); ?>

<?php echo $Html->link('Delete', array('action' => 'delete', 'parameters' => array($page->id))); ?>
          </span>
            <div class="clearl"></div>
            <div class="content">
            <?php echo $page->encode(
              'content',
              array('stripAll' => TRUE, 'maxLength' => 250, 'append' => '[...]')
            ); ?>
            </div>
          </div>
<?php endforeach; ?>
        </div>
      </div>

<?php
$this->render('backend/footer.html');
?>

