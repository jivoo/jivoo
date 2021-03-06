<?php
// Jivoo
// Copyright (c) 2015 Niels Sonnich Poulsen (http://nielssp.dk)
// Licensed under the MIT license.
// See the LICENSE file or http://opensource.org/licenses/MIT for more information.
namespace Jivoo\Helpers;

use Jivoo\Models\Selection\BasicSelection;
use Jivoo\Helpers\Filtering\SelectionFilterVisitor;
use Jivoo\Helpers\Filtering\FilterParser;
use Jivoo\Helpers\Filtering\FilterScanner;
use Jivoo\Helpers\Filtering\RecordFilterVisitor;
use Jivoo\Models\BasicModel;
use Jivoo\Models\Model;

/**
 * Helper for filtering selections or arrays of records based on a query string.
 * @property-read string $query The search query.
 * @property-read string[] $primary Primary fields.
 */
class FilteringHelper extends Helper {
  /**
   * @var FilterScanner Scanner.
   */
  private $scanner = null;
  
  /**
   * @var FilterPaser Parser.
   */
  private $parser = null;
  
  /**
   * @var string[] Primary fields.
   */
  private $primary = array();
  
  /**
   * @var bool
   */
  private $localizedOperators = false;
  
  /**
   * {@inheritdoc}
   */
  public function __get($property) {
    switch ($property) {
      case 'query':
        return $this->request->query['filter'];
      case 'primary':
        return $this->$property;
    }
    return parent::__get($property);
  }

  /**
   * {@inheritdoc}
   */
  public function __isset($property) {
    switch ($property) {
      case 'query':
        return isset($this->request->query['filter']);
      case 'primary':
        return isset($this->$property);
    }
    return parent::__isset($property);
  }
  
  /**
   * Add primary field.
   * @param string $column Field name.
   */
  public function addPrimary($column) {
    $this->primary[] = $column;
  }
  
  /**
   * Remove primary field.
   * @param string $column Field nane.
   */
  public function removePrimary($column) {
    $this->primary = array_diff($this->primary, array($column));
  }
  
  /**
   * Enable localized (translated) operators.
   * @param bool $enable Enable/disable.
   */
  public function localizeOperators($enable = true) {
    $this->localizedOperators = $enable;
  }
  
  /**
   * Adds localized operators.
   */
  private function addLocalizedOperators() {
    $this->scanner->addOperator(tr('not'), '!');
    $this->scanner->addOperator(tr('and'), '&');
    $this->scanner->addOperator(tr('or'), '|');
    $this->scanner->addOperator(tr('contains'), 'contains');
    $this->scanner->addOperator(tr('before'), 'before');
    $this->scanner->addOperator(tr('after'), 'after');
    $this->scanner->addOperator(tr('in'), 'in');
    $this->scanner->addOperator(tr('on'), 'on');
    $this->scanner->addOperator(tr('at'), 'at');
  }

  /**
   * Apply filtering to a selection or an array of records.
   * @param BasicSelection|BasicRecord[] $selection Selection or array of records.
   * @param BasicModel $model Model (must be set if using {@see BasicSelection}
   * for the first parameter).
   * @return BasicSelection|BasicRecord[] Filtered selection or array of records.
   */
  public function apply($selection, BasicModel $model = null) {
    if (!isset($this->query) or empty($this->query))
      return $selection;
    if (!isset($this->scanner)) {
      $this->scanner = new FilterScanner();
      if ($this->localizedOperators)
        $this->addLocalizedOperators();
    }
    if (!isset($this->parser))
      $this->parser = new FilterParser();
    $tokens = $this->scanner->scan($this->query);
    if (count($tokens) == 0)
      return $selection;
    $root = $this->parser->parse($tokens);
    if ($selection instanceof BasicSelection) {
      if (!isset($model)) {
        assume($selection instanceof Model);
        $model = $selection;
      }
      $visitor = new SelectionFilterVisitor($this, $model);
      $selection = $selection->where($visitor->visit($root));
      return $selection;
    }
    else {
      $result = array();
      $visitor = new RecordFilterVisitor($this);
      foreach ($selection as $record) {
        $visitor->setRecord($record);
        if ($visitor->visit($root))
          $result[] = $record;
      }
      return $result;
    }
  }
}