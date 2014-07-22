<?php
class SelectionVisitor extends FilterVisitor {

  private $primary;

  public function __construct($primary) {
    $this->primary = $primary;
  }

  protected function visitFilter(FilterNode $node) {
    $condition = new Condition();
    foreach ($node->children as $child) {
      if ($child->operator == 'or') {
        $condition->orWhere($this->visit($child));
      }
      else {
        $condition->andWhere($this->visit($child));
      }
    }
    return $condition;
  }
  protected function visitNotTerm(NotTermNode $node) {
    return new NotCondition($this->visit($node->child));
  }
  protected function visitComparison(ComparisonNode $node) {
    /// @TODO check for existence of column. AND TYPE
    switch ($node->comparison) {
      case '=':
      case '!=':
      case '<=':
      case '>=':
      case '>':
      case '<':
        return new Condition($node->left . ' ' . $node->comparison . ' ?', $node->right);
      case 'contains':
        return new Condition($node->left . ' LIKE %s', '%' . $node->right . '%');
    }
  }
  protected function visitString(StringNode $node) {
    // Foreach search column add condition
    return new Condition($this->primary . ' LIKE %s', '%' . $node->value . '%');
  }
}