<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity\Handler;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\l10n_server\Entity\ReleaseInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function \assert;
use function \array_merge;

/**
 * Provides the list builder handler for the Project entity.
 */
class ReleaseListBuilder extends EntityListBuilder {

  /**
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * @inheritDoc
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    $instance->dateFormatter = $container->get('date.formatter');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header = [];
    $header['label'] = ['data' => $this->t('Release')];
    $header['download_link'] = ['data' => $this->t('Download link')];
    $header['file_date'] = ['data' => $this->t('File date')];
    $header['last_parsed'] = ['data' => $this->t('Last parsed')];
    return array_merge($header, parent::buildHeader());
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    assert($entity instanceof ReleaseInterface);
    $row = [];
    $row['label']['data'] = $entity->label();
    if ($link = $entity->getDownloadLink()) {
      $row['download_link']['data'] = Link::fromTextAndUrl(
        Url::fromUri($link)->toString(),
        Url::fromUri($link)
      );
    }
    else {
      $row['homepage']['data'] = $this->t('n/a');
    }
    $row['file_date'] = ['data' => $entity->getFileDate() ? $this->dateFormatter->format($entity->getFileDate()) : '-'];
    $row['last_parsed']['data'] = $entity->getLastTimeParsed() ? $this->dateFormatter->format($entity->getLastTimeParsed()) : '-';
    return array_merge($row, parent::buildRow($entity));
  }

  /**
   * {@inheritdoc}
   */
  protected function getTitle(): TranslatableMarkup {
    return $this->t('Releases');
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->accessCheck(TRUE)
      ->sort($this->entityType->getKey('id'));

    // Add query condition with project from request.
    $params = \Drupal::routeMatch()->getParameters()->all();
    foreach ($params as $param) {
      if ($param instanceof \Drupal\l10n_server\Entity\Project) {
        /** \Drupal\l10n_server\Entity\Project $param */
        $query->condition('pid', $param->id());
        break;
      }
    }

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

}
