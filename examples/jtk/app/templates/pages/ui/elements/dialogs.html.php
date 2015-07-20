<?php $this->view->data->title = tr('Dialogs'); ?>
 
<div class="block">
  <div class="block-header"><h2>Dialogs</h2></div>
  <div class="block-content">
<p class="dialogs-demo">
<?php echo $Icon->button(tr('Info'), 'info', array('data-type' => 'info')); ?>

<?php echo $Icon->button(tr('Question'), 'question', array('data-type' => 'question')); ?>

<?php echo $Icon->button(tr('Success'), 'checkmark', array('data-type' => 'success')); ?>

<?php echo $Icon->button(tr('Warning'), 'warning', array('data-type' => 'warning')); ?>

<?php echo $Icon->button(tr('Error'), 'close', array('data-type' => 'error')); ?>
</p>
  </div>
</div>

<div class="block">
  <div class="block-header"><h2>Modals</h2></div>
  <div class="block-content">
<p class="modals-demo">
<?php echo $Icon->button(tr('Info'), 'info', array('data-type' => 'info')); ?>

<?php echo $Icon->button(tr('Question'), 'question', array('data-type' => 'question')); ?>

<?php echo $Icon->button(tr('Success'), 'checkmark', array('data-type' => 'success')); ?>

<?php echo $Icon->button(tr('Warning'), 'warning', array('data-type' => 'warning')); ?>

<?php echo $Icon->button(tr('Error'), 'close', array('data-type' => 'error')); ?>
</p>
  </div>
</div>

<div class="block dialog" id="block" style="display: none">
<div class="block-header">A dialog 
  <div class="block-toolbar">
    <?php echo $Icon->iconLink('Close', 'void:', 'close', array('class' => 'close')); ?>
  </div>
</div>
<div class="block-content">
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec sed pharetra lorem. Nunc auctor luctus tellus a faucibus. Quisque dictum in eros sed consequat.</p>
<p>Vestibulum consequat, ipsum at porttitor iaculis, nibh neque accumsan dui, sed sodales orci ligula eu mauris.</p>
</div>
<div class="block-footer">
  <?php echo $Icon->button('Cancel', 'close'); ?>
  <?php echo $Icon->button('OK', 'checkmark', array('class' => 'primary')); ?>
</div>
</div>


<script type="text/javascript">
$(function() {
  var $popup = $('#block').clone();
  $popup.show();
  $('.dialogs-demo button').click(function() {
    var $this = $popup.clone();
    $this.addClass('block-' + $(this).data('type'));
    $this.find('button').click($.magnificPopup.close);
    $this.find('.block-toolbar a.close').click(function() {
      $.magnificPopup.close();
      return false;
    });
    $.magnificPopup.open({
      closeBtnInside: false,
      showCloseBtn: false,
      prependTo: $('#main'),
      alignTop: true,
      items: {
        src: $this,
        type: 'inline'
      }
    });
  });
  $('.modals-demo button').click(function() {
    var $this = $popup.clone();
    $this.addClass('block-' + $(this).data('type'));
    $this.find('button').click($.magnificPopup.close);
    $this.find('.block-toolbar a.close').remove();
    $.magnificPopup.open({
      closeBtnInside: false,
      showCloseBtn: false,
      modal: true,
      prependTo: $('#main'),
      alignTop: true,
      items: {
        src: $this,
        type: 'inline'
      }
    });
  });
});
</script>
