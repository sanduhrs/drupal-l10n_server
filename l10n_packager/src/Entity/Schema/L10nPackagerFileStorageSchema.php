<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity\Storage;

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Storage schema class.
 */
class L10nPackagerFileStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping): array {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name === 'l10n_packager_file') {
      if ($field_name === 'rid') {
        $this->addSharedTableFieldUniqueKey($storage_definition, $schema);
      }
      if ($field_name === 'fid') {
        $this->addSharedTableFieldUniqueKey($storage_definition, $schema);
      }
    }
    return $schema;
  }

}
