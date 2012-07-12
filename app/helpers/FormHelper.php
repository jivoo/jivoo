<?php

class FormHelper extends ApplicationHelper {

  private $record = NULL;
  private $currentForm = '';
  private $post = FALSE;
  private $errors = array();
  
  public function begin(IModel $record, $fragment = NULL) {
    $this->post = $this->request->isPost();
    $this->record = $record;
    if ($this->post) {
      $this->errors = $record->getErrors();
    }
    $this->currentForm = classFileName(get_class($record));
    return '<form action="' . $this->getLink(array('fragment' => $fragment)) . '" method="post">';
  }

  public function fieldName($field) {
    return $this->currentForm . '[' . $field . ']';
  }

  public function fieldId($field, $value = NULL) {
    $id = $this->currentForm . '_' . $field;
    if (isset($value)) {
      $id .= '_' . $value;
    }
    return $id;
  }
  
  public function isRequired($field, $output = NULL) {
    $required = $this->record->isFieldRequired($field);
    if (isset($output)) {
      return $required ? $output : '';
    }
    else {
      return $required;
    }
  }
  
  public function isValid($field = NULL) {
    if (!$this->post) {
      return TRUE;
    }
    if (isset($field)) {
      return !isset($this->errors[$field]);
    }
    else {
      return count($this->errors) < 1;
    }
  }
  
  public function getErrors() {
    return $this->errors;
  }
  
  public function getError($field) {
    return isset($this->errors[$field]) ? $this->errors[$field] : '';
  }

  public function label($field, $label = NULL,  $options = array()) {
    $html = '<label for="' . $this->fieldId($field) . '"';
    if (isset($options['class'])) {
      $html .= ' class="' . $options['class'] . '"';
    }
    if (!isset($label)) {
      $label = $this->record->getFieldLabel($field);
    }
    $html .= '>' . $label . '</label>' . PHP_EOL;
    return $html;
  }

  public function field($field, $options = array()) {
    $type = $this->record->getFieldType($field);
    switch ($type) {
      case 'text':
        return $this->textarea($field, $options);
      default:
        if (strpos($field, 'pass') !== FALSE) {
          return $this->password($field, $options);
        }
        return $this->text($field, $options);
    }
  }

  public function fieldValue($field) {
    if (isset($this->record->$field)) {
      return h($this->record->$field);
    }
    return '';
  }

  private function addAttributes($options) {
    $html = '';
    foreach ($options as $attribute => $value) {
      $html .= ' ' . $attribute . '="' . $value . '"';
    }
    return $html;
  }

  public function text($field, $options = array()) {
    $html = '<input type="text" name="' . $this->fieldName($field) .'"';
    $html .= ' id="' . $this->fieldId($field) . '"';
    if (!isset($options['class'])) {
      $options['class'] = 'text';
    }
    $html .= $this->addAttributes($options);
    if ($this->fieldValue($field) != '') {
      $html .= ' value="' . $this->fieldValue($field) . '"';
    }
    $html .= ' />' . PHP_EOL;
    return $html;
  }

  public function radio($field, $value, $options = array()) {
    $html = '<input type="radio" name="' . $this->fieldName($field) .'"';
    $html .= ' id="' . $this->fieldId($field, $value) . '"';
    $html .= ' value="' . $value . '"';
    $html .= $this->addAttributes($options);
    if ($this->fieldValue($field) == $value) {
      $html .= ' checked="checked"';
    }
    $html .= ' /> ' . PHP_EOL;
    return $html;
  }

  public function password($field, $options = array()) {
    $html = '<input type="password" name="' . $this->fieldName($field) .'"';
    $html .= ' id="' . $this->fieldId($field) . '"';
    if (!isset($options['class'])) {
      $options['class'] = 'text';
    }
    $html .= $this->addAttributes($options);
    $html .= ' />' . PHP_EOL;
    return $html;
  }

  public function textarea($field, $options = array()) {
    $html = '<textarea name="' . $this->fieldName($field) .'"';
    $html .= ' id="' . $this->fieldId($field) . '"';
    $html .= $this->addAttributes($options);
    $html .= '>';
    if ($this->fieldValue($field) != '') {
      $html .= $this->fieldValue($field);
    }
    $html .= '</textarea>' . PHP_EOL;
    return $html;
  }

  public function submit($value, $field = 'submit', $options = array()) {
    $html = '<input type="submit" name="' . $field . '"';
    $html .= ' id="' . $this->fieldId($field) . '"';
    $html .= ' value="' . $value . '"';
    if (!isset($options['class'])) {
      $options['class'] = 'button';
    }
    $html .= $this->addAttributes($options);
    $html .= ' /> ' . PHP_EOL;
    return $html;
  }
    
  public function end() {
    return '</form>';
  }
}
