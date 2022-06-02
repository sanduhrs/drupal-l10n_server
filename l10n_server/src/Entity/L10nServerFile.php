<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the file entity class.
 *
 * @ContentEntityType(
 *   id = "l10n_server_file",
 *   label = @Translation("File"),
 *   label_collection = @Translation("Files"),
 *   label_singular = @Translation("file"),
 *   label_plural = @Translation("files"),
 *   label_count = @PluralTranslation(
 *     singular = "@count files",
 *     plural = "@count files",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\l10n_server\Entity\ListBuilder\L10nServerFileListBuilder",
 *     "storage_schema" = "Drupal\l10n_server\Entity\Storage\L10nServerFileStorageSchema",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\l10n_server\Form\L10nServerFileForm",
 *       "edit" = "Drupal\l10n_server\Form\L10nServerFileForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\l10n_server\Routing\L10nServerFileHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "l10n_server_file",
 *   admin_permission = "administer l10n server file",
 *   entity_keys = {
 *     "id" = "fid",
 *     "label" = "location",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/localization/l10n-server-file",
 *     "add-form" = "/l10n-server-file/add",
 *     "canonical" = "/l10n-server-file/{l10n_server_file}",
 *     "edit-form" = "/l10n-server-file/{l10n_server_file}",
 *     "delete-form" = "/l10n-server-file/{l10n_server_file}/delete",
 *   },
 * )
 */
class L10nServerFile extends ContentEntityBase implements L10nServerFileInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['pid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Project'))
      ->setRequired(TRUE)
      ->setDescription(t('Reference to the {l10n_server_project}.pid of the parent project.'))
      ->setSetting('target_type', 'l10n_server_project')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);
    $fields['rid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Release'))
      ->setRequired(TRUE)
      ->setDescription(t('Reference to the {l10n_server_release}.rid of the parent release.'))
      ->setSetting('target_type', 'l10n_server_release')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);
    $fields['location'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Location'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);
    $fields['revision'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Revision'))
      ->setDescription('CVS revision number extracted for reuse in exports.')
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);
    return $fields;
  }

}
