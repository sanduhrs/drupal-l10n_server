<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity\Storage;

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Storage schema class.
 */
class L10nServerStatusFlagStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   *
   * @todo Fix index and primary key: sid_language_has_suggestion, sid_language_has_translation.
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping): array {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name === 'l10n_server_status_flag') {
      switch ($field_name) {
        case 'language':
        case 'has_suggestion':
        case 'has_translation':
          $schema['fields'][$field_name]['not null'] = TRUE;
      }
    }
    return $schema;
  }

}
