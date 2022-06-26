<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the history entity class.
 *
 * @ContentEntityType(
 *   id = "l10n_server_translation_history",
 *   label = @Translation("History"),
 *   label_collection = @Translation("Histories"),
 *   label_singular = @Translation("history"),
 *   label_plural = @Translation("histories"),
 *   label_count = @PluralTranslation(
 *     singular = "@count histories",
 *     plural = "@count histories",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\l10n_server\Entity\ListBuilder\L10nServerTranslationHistoryListBuilder",
 *     "storage_schema" = "Drupal\l10n_server\Entity\Storage\L10nServerTranslationHistoryStorageSchema",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\l10n_server\Form\L10nServerTranslationHistoryForm",
 *       "edit" = "Drupal\l10n_server\Form\L10nServerTranslationHistoryForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\l10n_server\Routing\L10nServerTranslationHistoryHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "l10n_server_translation_history",
 *   admin_permission = "administer l10n server translation history",
 *   entity_keys = {
 *     "id" = "tid",
 *     "label" = "uuid",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/localization/l10n-server-translation-history",
 *     "add-form" = "/l10n-server-translation-history/add",
 *     "canonical" = "/l10n-server-translation-history/{l10n_server_translation_history}",
 *     "edit-form" = "/l10n-server-translation-history/{l10n_server_translation_history}",
 *     "delete-form" = "/l10n-server-translation-history/{l10n_server_translation_history}/delete",
 *   },
 * )
 */
class L10nServerTranslationHistory extends ContentEntityBase implements L10nServerTranslationHistoryInterface {

  /**
   * Action flag for string addition.
   */
  const ACTION_ADD = 1;

  /**
   * Action flag for approval.
   */
  const ACTION_APPROVE = 2;

  /**
   * Action flag for denial.
   */
  const ACTION_DECLINE = 3;

  /**
   * Action flag for demotes.
   */
  const ACTION_DEMOTE = 4;

  /**
   * Action flag for re-additions.
   */
  const ACTION_READD = 5;

  /**
   * Action medium flag for unknown sources.
   */
  const MEDIUM_UNKNOWN = 0;

  /**
   * Action medium flag for web based actions.
   */
  const MEDIUM_WEB = 1;

  /**
   * Action medium flag for web based import.
   */
  const MEDIUM_IMPORT = 2;

  /**
   * Action medium flag for remote action.
   */
  const MEDIUM_REMOTE = 3;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['uid_action'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setDescription('Reference to the {users}.uid who performed the action.')
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);
    $fields['time_action'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('Unix timestamp of time when the action happened.'))
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
    $fields['type_action'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Type action'))
      ->setDescription(t('Numeric identifier of the action that happened.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'number_integer',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ]);
    $fields['medium_action'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Medium action'))
      ->setDescription(t('Numeric identifier of the medium the action happened through.'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'number_integer',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ]);
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getActionUid(): int {
    return $this->get('uid_action')->first()->getValue()['target_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function setActionUid(int $uid): self {
    $this->set('uid_action', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getActionTime(): int {
    return $this->get('time_action')->first()->getValue()['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function setActionTime(int $time): self {
    $this->set('time_action', $time);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getActionType(): string {
    return $this->get('type_action')->first()->getValue()['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function setActionType(string $type): self {
    $this->set('type_action', $type);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getActionMedium(): string {
    return $this->get('medium_action')->first()->getValue()['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function setActionMedium(string $medium): self {
    $this->set('medium_action', $medium);
    return $this;
  }

}
