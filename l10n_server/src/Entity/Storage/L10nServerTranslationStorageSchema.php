<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity\Storage;

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Storage schema class.
 */
class L10nServerTranslationStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping): array {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name === 'l10n_server_translation') {
      switch ($field_name) {
        case 'sid':
        case 'language':
        case 'uid':
        case 'created':
        case 'changed':
        case 'suggestion':
        case 'status':
          $schema['fields'][$field_name]['not null'] = TRUE;
      }
      if ($field_name === 'uid') {
        $this->addSharedTableFieldIndex($storage_definition, $schema);
      }
    }
    return $schema;
  }

}
