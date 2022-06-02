<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the line entity class.
 *
 * @ContentEntityType(
 *   id = "l10n_server_line",
 *   label = @Translation("Line"),
 *   label_collection = @Translation("Lines"),
 *   label_singular = @Translation("line"),
 *   label_plural = @Translation("lines"),
 *   label_count = @PluralTranslation(
 *     singular = "@count lines",
 *     plural = "@count lines",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\l10n_server\Entity\ListBuilder\L10nServerLineListBuilder",
 *     "storage_schema" = "Drupal\l10n_server\Entity\Storage\L10nServerLineStorageSchema",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\l10n_server\Form\L10nServerLineForm",
 *       "edit" = "Drupal\l10n_server\Form\L10nServerLineForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\l10n_server\Routing\L10nServerLineHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "l10n_server_line",
 *   admin_permission = "administer l10n server line",
 *   entity_keys = {
 *     "id" = "lid",
 *     "label" = "lid",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/localization/l10n-server-line",
 *     "add-form" = "/l10n-server-line/add",
 *     "canonical" = "/l10n-server-line/{l10n_server_line}",
 *     "edit-form" = "/l10n-server-line/{l10n_server_line}",
 *     "delete-form" = "/l10n-server-line/{l10n_server_line}/delete",
 *   },
 * )
 */
class L10nServerLine extends ContentEntityBase implements L10nServerLineInterface {

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
    $fields['fid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('File'))
      ->setRequired(TRUE)
      ->setDescription(t('Reference to the {l10n_server_file}.fid of the parent file.'))
      ->setSetting('target_type', 'l10n_server_file')
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
    $fields['lineno'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Line no.'))
      ->setDescription(t('Number of line where the string occurrence was found.'))
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);
    $fields['sid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('String'))
      ->setRequired(TRUE)
      ->setDescription(t('Reference to the {l10n_server_string}.sid found on this line.'))
      ->setSetting('target_type', 'l10n_server_string')
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
    $fields['type'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Type of occurrence'))
      ->setDescription(t('Type of occurance. Possible values are constants POTX_STRING_INSTALLER, POTX_STRING_RUNTIME or POTX_STRING_BOTH.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'number_integer',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);
    return $fields;
  }

}
