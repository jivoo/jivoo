<?php
/**
 * Describes the format of data in a record
 * @package Core\Models
 */
interface IBasicModel {
  /** @return string Name of model */
  public function getName();

  /** @return string[] List of field names */
  public function getFields();

  /**
   * Get type of field
   * @param string $field Field name
   * @return DataType|null Type of field if it exists
   */
  public function getType($field);

  /**
   * Get editor
   * @param string $field Field name
   * @return IEditor|null An editor if it exists
   */
  public function getEditor($field);

  /**
   * Get field label
   * @param string $field Field label
   * @return string A translated name for the field
   */
  public function getLabel($field);

  /**
   * Determine if the field exists in the model
   * @param string $field Field name
   * @return bool True if the field exists, false otherwise
   */
  public function hasField($field);

  /**
   * Determine if the field is required
   * @param string $field Field name
   * @return bool True if required, false otherwise
   */
  public function isRequired($field);
}
