<?php
declare(strict_types=1);

namespace Drupal\l10n_server;

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

class ProjectStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping): array {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name === 'l10n_server_project') {
      switch ($field_name) {
        case 'title':
        case 'connector_module':
        case 'weight':
          $schema['fields'][$field_name]['not null'] = TRUE;
          break;
      }
      if ($field_name === 'weight') {
        $schema['fields']['weight']['default'] = 0;
      }
      else if ($field_name === 'uri') {
        $this->addSharedTableFieldUniqueKey($storage_definition, $schema);
      }
      else if ($field_name === 'connector_module') {
        $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
      }
      else if ($field_name === 'last_parsed') {
        $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
      }
    }
    return $schema;
  }

}
