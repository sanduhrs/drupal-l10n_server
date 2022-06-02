<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity\Storage;

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Storage schema class.
 */
class L10nServerLineStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   *
   * @todo Add a 'indexes' => ['pid_sid' => ['pid', 'sid']] to the table.
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping): array {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name === 'l10n_server_line') {
      if ($field_name === 'type') {
        $schema['fields'][$field_name]['not null'] = TRUE;
      }
      elseif ($field_name === 'pid') {
        $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
      }
      elseif ($field_name === 'fid') {
        $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
      }
      elseif ($field_name === 'rid') {
        $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
      }
      elseif ($field_name === 'sid') {
        $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
      }
    }
    return $schema;
  }

}
