<?php

class ActiveCollection extends Model {
  private $model;
  private $recordId;
  private $other;
  private $thisKey;
  private $otherKey;
  private $source;
  private $join = null;
  private $otherPrimary;

  public function __construct(ActiveModel $thisModel, $recordId, $association) {
    $this->model = $thisModel;
    $this->recordId = $recordId;
    $this->other = $association['model'];
    $this->thisKey = $association['thisKey'];
    $this->otherKey = $association['otherKey'];
    if (isset($association['join'])) {
      $this->join = $association['join'];
      $this->otherPrimary = $association['otherPrimary'];
    }
    $this->source = $this->prepareSelection($this->other);
  }

  private function prepareSelection(IBasicSelection $selection = null) {
    if (!isset($selection))
      return $this->source;
    if (isset($this->join)) {
      assume($selection instanceof IReadSelection);
      return $selection
        ->leftJoin($this->join, $this->otherPrimary . '= J.' . $this->otherKey, 'J')
        ->where('J.' . $this->thisKey . ' = ?', $this->recordId);
    }
    else {
      $selection = $selection->where($this->thisKey . ' = ?', $this->recordId);
      if ($selection instanceof Selection)
        $selection = $selection->toReadSelection();
      return $selection;
    }
  }

  public function add(ActiveRecord $record) {
    if (isset($this->join)) {
      $pk = $this->otherPrimary;
      $this->join->insert(array(
        $this->thisKey => $this->recordId,
        $this->otherKey => $record->$pk
      ));
    }
    else {
      $key = $this->thisKey;
      $record->$key = $this->recordId;
      $record->save();
    }
  }

  public function addAll(ISelection $selection) {
    if (isset($this->join)) {
      $pk = $this->otherPrimary;
      foreach ($selection as $record) {
        $this->join->insert(array(
          $this->thisKey => $this->recordId,
          $this->otherKey => $record->$pk
        ));
      }
    }
    else {
      $selection->set($this->thisKey, $this->recordId)->update();
    }
  }

  public function contains(ActiveRecord $record) {
    if (isset($this->join)) {
      $pk = $this->otherPrimary;
      return $this->join->where($this->thisKey . ' = ?', $this->recordId)
        ->and($this->otherKey . ' = ?', $record->$pk)
        ->count() > 0;
    }
    else {
      return $this->source->where($this->thisKey . ' = ?', $this->recordId)
        ->count() > 0;
    }
  }

  public function remove(ActiveRecord $record) {
    if (isset($this->join)) {
      $pk = $this->otherPrimary;
      return $this->join->where($this->thisKey . ' = ?', $this->recordId)
        ->and($this->otherKey . ' = ?', $record->$pk)
        ->delete();
    }
    else {
      $key = $this->thisKey;
      $record->$key = null;
      $record->save();
    }
  }

  public function removeAll(ISelection $selection) {
    if (isset($this->join)) {
      $pk = $this->otherPrimary;
      foreach ($selection as $record) {
        return $this->join->where($this->thisKey . ' = ?', $this->recordId)
          ->and($this->otherKey . ' = ?', $record->$pk)
          ->delete();
      }
    }
    else {
      $this->prepareSelection($selection)->set($this->thisKey, null)->update();
    }
  }

  public function getName() {
    return $this->other->getName();
  }
  
  public function getSchema() {
    return $this->other->getSchema();
  }

  public function create($data = array(), $allowedFields = null) {
    $record = $this->other->create($data, $allowedFields);
    if (!isset($this->join)) {
      $thisKey = $this->thisKey;
      $record->$thisKey = $this->recordId;
    }
    return $record;
  }
  
  public function createExisting($data = array()) {
    return $this->other->createExisting($data, $allowedFields);
  }

  public function updateSelection(UpdateSelection $selection) {
    if (!isset($this->join))
      return $this->other->updateSelection(
        $selection->where($this->thisKey . ' = ?', $this->recordId)
      );
    $sets = $selection->sets;
    $read = $this->prepareSelection($selection->toSelection());
    $num = 0;
    foreach ($read->select($this->otherPrimary) as $otherId) {
      $num++;
      $this->other->where($this->otherPrimary . ' = ?', $otherId)
        ->set($sets)
        ->update();
    }
    return $num;
  }
  
  public function deleteSelection(DeleteSelection $selection) {
    if (!isset($this->join))
      return $this->other->deleteSelection($this->prepareSelection($selection));
    $pk = $this->otherPrimary;
    $num = 0;
    if (isset($selection))
      $selection = $this->prepareSelection($selection->toSelection());
    else
      $selection = $this->source;
    foreach ($selection as $record) {
      $num++;
      $this->join->where($this->thisKey . ' = ?', $this->recordId)
        ->and($this->otherKey . ' = ?', $record->$pk)
        ->delete();
      $record->delete();
    }
    return $num;
  }
  
  public function countSelection(ReadSelection $selection) {
    return $this->other->countSelection($this->prepareSelection($selection));
  }
  
  public function firstSelection(ReadSelection $selection) {
    return $this->other->firstSelection($this->prepareSelection($selection));
  }
  
  public function lastSelection(ReadSelection $selection) {
    return $this->other->lastSelection($this->prepareSelection($selection));
  }

  public function read(ReadSelection $selection) {
    return $this->other->read($this->prepareSelection($selection));
  }

  public function readCustom(ReadSelection $selection) {
    return $this->other->readCustom($this->prepareSelection($selection));
  }
  
  public function insert($data) {
    if (!isset($this->join)) {
      $data[$this->thisKey] = $this->recordId;
    }
    $insertId = $this->other->insert($data);
    if (isset($this->join)) {
      $pk = $this->other->getAiPrimaryKey();
      if (isset($pk))
        $data[$pk] = $insertId;
      $this->join->insert(array(
        $this->thisKey => $this->recordId,
        $this->otherKey => $data[$this->otherPrimary]
      ));
    }
    return $insertId;
  }
}
