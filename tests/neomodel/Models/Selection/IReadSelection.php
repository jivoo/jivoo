<?php
interface IReadSelection extends IBasicSelection, Iterator {
  /**
   * @param string $column
   * @return IReadSelection
   */
  public function select($column, $alias = null);
  
  public function selectAll();
  /**
   * @param string|string[] $columns
   * @param string $condition
   * @return IReadSelection
  */
  public function groupBy($columns, $condition = null);

  // joins
  public function innerJoin(IModel $other, $condition, $alias = null);
  public function leftJoin(IModel $other, $condition, $alias = null);
  public function rightJoin(IModel $other, $condition, $alias = null);

  /**
   * @return IRecord
  */
  public function first();
  /**
   * @return IRecord
  */
  public function last();

  /**
   * @return int
  */
  public function count();
  /**
   * Set offset
   * @param int $offset Offset
   * @return IReadSelection Self
  */
  public function offset($offset);
}