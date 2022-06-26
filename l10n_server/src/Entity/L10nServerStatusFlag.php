<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the status flag entity class.
 *
 * @ContentEntityType(
 *   id = "l10n_server_status_flag",
 *   label = @Translation("Status Flag"),
 *   label_collection = @Translation("Status Flags"),
 *   label_singular = @Translation("status flag"),
 *   label_plural = @Translation("status flags"),
 *   label_count = @PluralTranslation(
 *     singular = "@count status flags",
 *     plural = "@count status flags",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\l10n_server\Entity\ListBuilder\L10nServerStatusFlagListBuilder",
 *     "storage_schema" = "Drupal\l10n_server\Entity\Storage\L10nServerStatusFlagStorageSchema",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\l10n_server\Form\L10nServerStatusFlagForm",
 *       "edit" = "Drupal\l10n_server\Form\L10nServerStatusFlagForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\l10n_server\Routing\L10nServerStatusFlagHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "l10n_server_status_flag",
 *   admin_permission = "administer l10n server status flag",
 *   entity_keys = {
 *     "id" = "sid",
 *     "label" = "uuid",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/localization/l10n-server-status-flag",
 *     "add-form" = "/l10n-server-status-flag/add",
 *     "canonical" = "/l10n-server-status-flag/{l10n_server_status_flag}",
 *     "edit-form" = "/l10n-server-status-flag/{l10n_server_status_flag}",
 *     "delete-form" = "/l10n-server-status-flag/{l10n_server_status_flag}/delete",
 *   },
 * )
 */
class L10nServerStatusFlag extends ContentEntityBase implements L10nServerStatusFlagInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
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
    $fields['has_suggestion'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Has suggestion'))
      ->setDescription(t('Cached status flag of whether there is at least one row in {l10n_server_translation} where is_suggestion = 1, is_active = 1 and sid and language is the same as this one.'))
      ->setDefaultValue(0);
    $fields['has_translation'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Has translation'))
      ->setDescription(t('Cached status flag of whether there is at least one row in {l10n_server_translation} where is_suggestion = 0, is_active = 1, translation is not empty and sid and language is the same as this one.'))
      ->setDefaultValue(0);
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguage(): string {
    return $this->get('language')->first()->getValue()['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function setLanguage(string $language): L10nServerStatusFlagInterface {
    $this->set('language', $language);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTranslationString(): bool {
    return (bool) $this->get('has_translation')->first()->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function hasSuggestionString(): bool {
    return (bool) $this->get('has_suggestion')->first()->getValue();
  }

}
