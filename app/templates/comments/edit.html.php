<?php
// Render the header
$this->render('backend/header.html');
?>
      
    <?php echo $Form->begin($comment); ?>

      <div class="section">
        <div class="container">
          <p>
            <?php echo $Form->label('author', NULL, array('class' => 'small')); ?>
            <?php echo $Form ->field('author'); ?>
          </p>
        </div>
      </div>
      
      <div class="section">
        <div class="container">
          <p>
            <?php echo $Form->label('email', NULL, array('class' => 'small')); ?>
            <?php echo $Form ->field('email'); ?>
          </p>
        </div>
      </div>
      
      <div class="section">
        <div class="container">
          <p>
            <?php echo $Form->label('website', NULL, array('class' => 'small')); ?>
            <?php echo $Form ->field('website'); ?>
          </p>
        </div>
      </div>
    
      <div class="section">
        <div class="container">
          <p>
            <?php echo $Form->label('content', NULL, array('class' => 'small')); ?>
            <?php echo $Form ->field('content'); ?>
          </p>
        </div>
      </div>
      
      <div class="section">
        <div class="container">
          <div class="aright">
            <?php echo $Form->submit(tr('Save'), 'save', array('class' => 'button publish')); ?>
          </div>
        </div>
      </div>
    <?php echo $Form->end(); ?>

<?php
$this->render('backend/footer.html');
?>

