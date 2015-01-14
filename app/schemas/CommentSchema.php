<?php
/**
 * @package PeanutCMS\Schemas
 */
class CommentSchema extends Schema {
  protected function createSchema() {
    $this->addAutoIncrementId();
    $this->postId = DataType::integer(DataType::UNSIGNED);
    $this->userId = DataType::integer(DataType::UNSIGNED, true);
    $this->parentId = DataType::integer(DataType::UNSIGNED, true);
    $this->author = DataType::string(255);
    $this->email = DataType::string(255);
    $this->website = DataType::string(255, true);
    $this->content = DataType::text();
    $this->contentText = DataType::text();
    $this->contentHtml = DataType::text();
    $this->contentFormat = DataType::string(255, false, 'html');
    $this->ip = DataType::string(255);
    $this->status = DataType::enum('CommentStatus', false, 'pending');
    $this->addTimeStamps();
    $this->addIndex('postId', 'postId');
  }
}
