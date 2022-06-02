<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity\Storage;

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Storage schema class.
 */
class L10nServerProjectStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   *
   * @todo Add a 'unique keys' => ['uri_connector_module' => ['uri', 'connector_module']] to the table.
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping): array {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name === 'l10n_server_project') {
      switch ($field_name) {
        case 'title':
        case 'connector_module':
        case 'weight':
        case 'status':
          $schema['fields'][$field_name]['not null'] = TRUE;
          break;
      }
      if ($field_name === 'connector_module') {
        $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
      }
      elseif ($field_name === 'last_parsed') {
        $schema['fields'][$field_name]['not null'] = FALSE;
        $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
      }
      elseif ($field_name === 'uri') {
        $this->addSharedTableFieldUniqueKey($storage_definition, $schema);
      }
      elseif ($field_name === 'weight') {
        $schema['fields']['weight']['default'] = 0;
        $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
      }
    }
    return $schema;
  }

}
