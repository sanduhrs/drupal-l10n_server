<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity\Storage;

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Storage schema class.
 */
class L10nServerReleaseStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping): array {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name === 'l10n_server_release') {
      switch ($field_name) {
        case 'title':
        case 'download_link':
        case 'weight':
        case 'sid_count':
          $schema['fields'][$field_name]['not null'] = TRUE;
          break;
      }
      if ($field_name === 'pid') {
        $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
      }
      elseif ($field_name === 'weight') {
        $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
      }
    }
    return $schema;
  }

}
