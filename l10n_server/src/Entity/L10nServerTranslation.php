<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the translation entity class.
 *
 * @ContentEntityType(
 *   id = "l10n_server_translation",
 *   label = @Translation("Translation"),
 *   label_collection = @Translation("Translations"),
 *   label_singular = @Translation("translation"),
 *   label_plural = @Translation("translations"),
 *   label_count = @PluralTranslation(
 *     singular = "@count translations",
 *     plural = "@count translations",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\l10n_server\Entity\ListBuilder\L10nServerTranslationListBuilder",
 *     "storage_schema" = "Drupal\l10n_server\Entity\Storage\L10nServerTranslationStorageSchema",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\l10n_server\Form\L10nServerTranslationForm",
 *       "edit" = "Drupal\l10n_server\Form\L10nServerTranslationForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\l10n_server\Routing\L10nServerTranslationHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "l10n_server_translation",
 *   admin_permission = "administer l10n server translation",
 *   entity_keys = {
 *     "id" = "tid",
 *     "label" = "uuid",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/localization/l10n-server-translation",
 *     "add-form" = "/l10n-server-translation/add",
 *     "canonical" = "/l10n-server-translation/{l10n_server_translation}",
 *     "edit-form" = "/l10n-server-translation/{l10n_server_translation}",
 *     "delete-form" = "/l10n-server-translation/{l10n_server_translation}/delete",
 *   },
 * )
 */
class L10nServerTranslation extends ContentEntityBase implements L10nServerTranslationInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['sid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('String'))
      ->setRequired(TRUE)
      ->setDescription(t('Reference to the {l10n_server_string}.sid which is being translated.'))
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
    $fields['translation'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Translation'))
      ->setDescription('The actual translation or suggestion.')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(static::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the translation was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the translation was last edited.'));
    $fields['suggestion'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Suggestion'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Is suggestion')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);
    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getStringId(): int {
    return (int) $this->get('sid')->first()->getValue()['target_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function setStringId(int $sid): self {
    $this->set('sid', $sid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getString(): L10nServerString {
    return L10nServerString::load($this->getStringId());
  }

  /**
   * {@inheritdoc}
   */
  public function setString(L10nServerString $string): self {
    $this->setStringId($string->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguage(): string {
    return (string) $this->get('language')->first()->getValue()['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function setLanguage(string $language): self {
    $this->set('language', $language);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslationString(): string {
    return (string) $this->get('translation')->first()->getValue()['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function setTranslationString(string $translation): L10nServerTranslationInterface {
    $this->set('translation', $translation);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUid(): int {
    return (int) $this->get('uid')->first()->getValue()['target_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function setUid(int $uid): self {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreated(): int {
    return (int) $this->get('created')->first()->getValue()['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function setCreated(int $created): L10nServerTranslationInterface {
    $this->set('created', $created);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChanged(): int {
    return (int) $this->get('changed')->first()->getValue()['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function setChanged(int $changed): L10nServerTranslationInterface {
    $this->set('changed', $changed);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isSuggestion(): bool {
    return (bool) $this->get('suggestion')->first()->getValue()['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function setSuggestion(bool $suggestion): L10nServerTranslationInterface {
    $this->set('suggestion', $suggestion);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): int {
    return (int) $this->get('status')->first()->getValue()['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus(int $status): L10nServerTranslationInterface {
    $this->set('status', $status);
    return $this;
  }

}
