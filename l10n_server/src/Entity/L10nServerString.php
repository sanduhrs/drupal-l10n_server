<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the string entity class.
 *
 * @ContentEntityType(
 *   id = "l10n_server_string",
 *   label = @Translation("String"),
 *   label_collection = @Translation("Strings"),
 *   label_singular = @Translation("string"),
 *   label_plural = @Translation("strings"),
 *   label_count = @PluralTranslation(
 *     singular = "@count strings",
 *     plural = "@count strings",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\l10n_server\Entity\ListBuilder\L10nServerStringListBuilder",
 *     "storage_schema" = "Drupal\l10n_server\Entity\Storage\L10nServerStringStorageSchema",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\l10n_server\Form\L10nServerStringForm",
 *       "edit" = "Drupal\l10n_server\Form\L10nServerStringForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\l10n_server\Routing\L10nServerStringHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "l10n_server_string",
 *   admin_permission = "administer l10n server string",
 *   entity_keys = {
 *     "id" = "sid",
 *     "label" = "uuid",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/localization/l10n-server-string",
 *     "add-form" = "/l10n-server-string/add",
 *     "canonical" = "/l10n-server-string/{l10n_server_string}",
 *     "edit-form" = "/l10n-server-string/{l10n_server_string}",
 *     "delete-form" = "/l10n-server-string/{l10n_server_string}/delete",
 *   },
 * )
 */
class L10nServerString extends ContentEntityBase implements L10nServerStringInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['value'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Value'))
      ->setDescription('The actual translatable string. For strings with multiple plural versions, we store them as the same translatable with a \0 separator (unlike Drupal itself), because it is easier to match translations with them (for multiple plural versions) this way, and we can force people to translate both at once.')
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'text_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);
    $fields['context'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Context'))
      ->setDescription('The context this string applies to. Only applicable to some strings in Drupal 7 and its modules.')
      ->setSetting('max_length', 255)
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);
    $fields['hashkey'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hashkey'))
      ->setDescription('MD5 hash of the concatenation of value and context, used for quick lookups when these two are known (imports, new releases, remote submissions).')
      ->setSetting('max_length', 32)
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);
    return $fields;
  }

}
