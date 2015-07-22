<?php $this->view->data->title = tr('Form elements'); ?>
<form>
<div class="field">
<label>Static</label>
  Whatever
</div>
<div class="field">
<label>Text</label>
<input type="text" placeholder="Write something here" />
<div class="help">Write something above</div>
</div>
<div class="field">
<label>Password</label>
<input type="password" placeholder="Password" />
</div>
<div class="field">
<label>Email</label>
<input type="email" placeholder="Write an email" />
</div>
<div class="field">
<label>Textarea</label>
<textarea placeholder="Textarea"></textarea>
</div>
<div class="field">
<label>Disabled</label>
<input type="text" disabled="disabled" placeholder="This is disabled" />
</div>
<div class="field">
<label>Select</label>
<select size="1">
<option>Option 1</option>
<option>Option 2</option>
<option>Option 3</option>
</select>
</div>
<div class="field">
<label>Select multiple</label>
<select size="5" multiple>
<option>Option 1</option>
<option>Option 2</option>
<option>Option 3</option>
<option>Option 4</option>
<option>Option 5</option>
<option>Option 6</option>
</select>
</div>
<div class="field field-muted">
<label>Muted</label>
<input type="text" placeholder="Muted" />
<div class="help">Muted</div>
</div>
<div class="field field-primary">
<label>Primary</label>
<input type="text" placeholder="Primary" />
<div class="help">Primary</div>
</div>
<div class="field field-light">
<label>Light</label>
<input type="text" placeholder="Light" />
<div class="help">Light</div>
</div>
<div class="field field-dark">
<label>Dark</label>
<input type="text" placeholder="Dark" />
<div class="help">Dark</div>
</div>
<div class="field field-info">
<label>Info</label>
<input type="text" placeholder="Info" />
<div class="help">Info</div>
</div>
<div class="field field-success">
<label>Success</label>
<input type="text" placeholder="Success" />
<div class="help">Success</div>
</div>
<div class="field field-warning">
<label>Warning</label>
<input type="text" placeholder="Warning" />
<div class="help">Warning</div>
</div>
<div class="field field-error">
<label>Error</label>
<input type="text" placeholder="Error" />
<div class="help">Error</div>
</div>
<div class="field">
<label>Checkbox</label>
<label><input type="checkbox" />
Checkbox</label>
</div>
<div class="field">
<label>Checkbox list</label>
<ul class="checkbox-list">
<li><label><input type="checkbox" />
Option 1</label></li>
<li><label><input type="checkbox" />
Option 2</label></li>
<li><label><input type="checkbox" />
Option 3</label></li>
</ul>
</div>
<div class="field">
<label>Inline checkbox list</label>
<ul class="checkbox-list list-inline">
<li><label><input type="checkbox" />
Option 1</label></li>
<li><label><input type="checkbox" />
Option 2</label></li>
<li><label><input type="checkbox" />
Option 3</label></li>
</ul>
</div>
<div class="field">
<label>Radio list</label>
<ul class="checkbox-list">
<li><label><input type="radio" name="radio" />
Option 1</label></li>
<li><label><input type="radio" name="radio" />
Option 2</label></li>
<li><label><input type="radio" name="radio" />
Option 3</label></li>
</ul>
</div>
<div class="field">
<label>Inline radio list</label>
<ul class="checkbox-list list-inline">
<li><label><input type="radio" name="radio" />
Option 1</label></li>
<li><label><input type="radio" name="radio" />
Option 2</label></li>
<li><label><input type="radio" name="radio" />
Option 3</label></li>
</ul>
</div>
<div class="field">
<label>Disabled</label>
<ul class="checkbox-list list-inline">
<li><label><input type="checkbox" disabled="disabled" />
Checkbox</label></li>
<li><label><input type="radio" disabled="disabled" />
Radio</label></li>
</ul>
</div>
<div class="buttons">
<input type="submit" class="button-primary" />
</div>
</form>
