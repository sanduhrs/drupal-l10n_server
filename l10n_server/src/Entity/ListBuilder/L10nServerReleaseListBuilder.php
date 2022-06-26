<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity\ListBuilder;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\l10n_server\Entity\L10nServerProject;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for the release entity type.
 */
class L10nServerReleaseListBuilder extends EntityListBuilder {

  /**
   * Date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(
      ContainerInterface $container,
      EntityTypeInterface $entity_type
  ): self {
    $instance = parent::createInstance($container, $entity_type);
    $instance->dateFormatter = $container->get('date.formatter');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $query = $this->getStorage()
      ->getQuery()
      ->accessCheck(FALSE)
      ->count();

    // Add query condition with project from request.
    $params = \Drupal::routeMatch()->getParameters()->all();
    foreach ($params as $param) {
      if ($param instanceof L10nServerProject) {
        $query->condition('pid', $param->id());

        $project = L10nServerProject::load($param->id());
        $build['header'] = [
          '#type' => 'html_tag',
          '#tag' => 'h2',
          '#value' => $this->t('@project releases', [
            '@project' => $project->label(),
          ]),
        ];
        break;
      }
    }
    $total = $query->execute();

    $build['table'] = parent::render();
    $build['summary']['#markup'] = $this->t('Total releases: @total', [
      '@total' => $total,
    ]);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header = [];
    $header['pid'] = $this->t('Project');
    $header['label'] = $this->t('Release');
    $header['version'] = $this->t('Version');
    $header['download_link'] = $this->t('Download link');
    $header['sid_count'] = $this->t('Strings');
    $header['lid_count'] = $this->t('Lines');
    $header['fid_count'] = $this->t('Files');
    $header['eid_count'] = $this->t('Warnings');
    $header['queued'] = $this->t('Queued');
    $header['last_parsed'] = $this->t('Last parsed');
    $header['file_date'] = $this->t('File date');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\l10n_server\Entity\L10nServerReleaseInterface $entity */
    $row = [];
    $row['pid'] = $entity->getProjectId();
    $row['label'] = $entity->toLink();
    $row['version'] = $entity->getVersion();
    if ($link = $entity->getDownloadLink()) {
      $row['download_link'] = Link::fromTextAndUrl(
        Url::fromUri($link)->toString(),
        Url::fromUri($link)
      );
    }
    else {
      $row['homepage'] = $this->t('n/a');
    }
    $row['sid_count'] = $entity->getSourceStringCount();
    $row['lid_count'] = $entity->getLineCount();
    $row['fid_count'] = $entity->getFileCount();
    $row['eid_count'] = $entity->getErrorCount();
    $row['queued'] = $entity->getQueuedTime() ? $this->dateFormatter->format($entity->getQueuedTime()) : '-';
    $row['last_parsed'] = $entity->getLastParsed() ? $this->dateFormatter->format($entity->getLastParsed()) : '-';
    $row['file_date'] = $entity->getFileDate() ? $this->dateFormatter->format($entity->getFileDate()) : '-';
    return $row + parent::buildRow($entity);
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
  protected function getEntityIds(): array|int {
    $query = $this->getStorage()->getQuery()
      ->accessCheck(FALSE)
      ->sort('file_date', 'DESC');

    // Add query condition with project from request.
    $params = \Drupal::routeMatch()->getParameters()->all();
    foreach ($params as $param) {
      if ($param instanceof L10nServerProject) {
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
