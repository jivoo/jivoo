<?php
namespace Blog\Schemas;

use Jivoo\Databases\SchemaBuilder;
use Jivoo\Models\DataType;

class PostSchema extends SchemaBuilder {
  protected function createSchema() {
    $this->addAutoIncrementId();
    $this->title = DataType::string(255);
    $this->content = DataType::text();
    $this->addTimestamps();
    $this->addIndex('created', 'created');
  }
}
