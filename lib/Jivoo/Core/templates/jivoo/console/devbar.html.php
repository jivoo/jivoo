<?php 
$this->import('jivoo-console.js');
$this->import('jivoo-console.css');
?>

<div id="jivoo-dev-tools">
  <div class="jivoo-devbar">
    <div class="jivoo-devbar-handle">Development</div>
    <ul class="jivoo-devbar-tools">
    </ul>
    <ul class="jivoo-devbar-settings">
      <li>
        <label class="jivoo-devbar-autohide"><input type="checkbox" class="jivoo-devbar-fade"> Fade</label>
      </li>
      <li>
        <label class="jivoo-devbar-autohide"><input type="checkbox" class="jivoo-devbar-hide"> Hide</label>
      </li>
    </ul>
  </div>
  <div class="jivoo-dev-frame-container">
    <div class="jivoo-dev-frame">
      <div class="jivoo-dev-frame-content">
      </div>
    </div>
  </div>
</div>