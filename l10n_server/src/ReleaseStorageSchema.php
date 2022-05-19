<?php
declare(strict_types=1);

namespace Drupal\l10n_server;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

class ReleaseStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);
    if ($base_table = $this->storage->getBaseTable()) {
      $schema[$base_table]['unique keys'] += [
        'l10n_server_release__version' => ['rid', 'title'],
      ];
    }
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name === 'l10n_server_release') {
      switch ($field_name) {
        case 'title':
        case 'weight':
        case 'pid':
          $schema['fields'][$field_name]['not null'] = TRUE;
          break;
      }
      if ($field_name === 'weight') {
        $schema['fields']['weight']['default'] = 0;
      }
      else if ($field_name === 'sid_count') {
        $schema['fields']['sid_count']['default'] = 0;
      }
      else if ($field_name === 'download_link') {
        $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
      }
      else if ($field_name === 'file_date') {
        $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
      }
      else if ($field_name === 'last_parsed') {
        $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
      }
    }
    return $schema;
  }

}
