<?php
declare(strict_types=1);

namespace Drupal\l10n_packager\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the packager file entity class.
 *
 * @ContentEntityType(
 *   id = "l10n_packager_file",
 *   label = @Translation("Packager File"),
 *   label_collection = @Translation("Packager Files"),
 *   label_singular = @Translation("packager file"),
 *   label_plural = @Translation("packager files"),
 *   label_count = @PluralTranslation(
 *     singular = "@count packager files",
 *     plural = "@count packager files",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\l10n_packager\Entity\ListBuilder\L10nPackagerFileListBuilder",
 *     "storage_schema" = "Drupal\l10n_server\Entity\L10nPackagerFileStorageSchema",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\l10n_packager\Form\L10nPackagerFileForm",
 *       "edit" = "Drupal\l10n_packager\Form\L10nPackagerFileForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\l10n_packager\Routing\L10nPackagerFileHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "l10n_packager_file",
 *   admin_permission = "administer l10n packager file",
 *   entity_keys = {
 *     "id" = "drid",
 *     "label" = "uuid",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/l10n-packager-file",
 *     "add-form" = "/l10n-packager-file/add",
 *     "canonical" = "/l10n-packager-file/{l10n_packager_file}",
 *     "edit-form" = "/l10n-packager-file/{l10n_packager_file}",
 *     "delete-form" = "/l10n-packager-file/{l10n_packager_file}/delete",
 *   },
 * )
 */
class L10nPackagerFile extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
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
    $fields['language'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Language'))
      ->setDescription(t('Reference to the {languages}.language to which the string is being translated.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 12)
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
    $fields['fid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('File'))
      ->setRequired(TRUE)
      ->setDescription(t('Reference to {file_managed}.fid.'))
      ->setSetting('target_type', 'l10n_packager_file')
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
    $fields['sid_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Strings'))
      ->setDescription(t('Count of source strings exported in this file.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ]);
    $fields['checked'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Checked'))
      ->setDescription(t('Unix timestamp of last time translation for this language was checked.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);
    return $fields;
  }

}
