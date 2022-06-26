<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for the history entity type.
 */
class L10nServerTranslationHistoryListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['table'] = parent::render();
    $total = $this->getStorage()
      ->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    $build['summary']['#markup'] = $this->t('Total changes: @total', [
      '@total' => $total,
    ]);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['uid_action'] = $this->t('User ID');
    $header['time_action'] = $this->t('Time');
    $header['type_action'] = $this->t('Type');
    $header['medium_action'] = $this->t('Medium');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\l10n_server\Entity\L10nServerTranslationHistoryInterface $entity */
    $row['id'] = $entity->id();
    $row['uid_action'] = $entity->getActionUid();
    $row['time_action'] = $entity->getActionTime();
    $row['type_action'] = $entity->getActionTime();
    $row['medium_action'] = $entity->getActionMedium();
    return $row + parent::buildRow($entity);
  }

}
