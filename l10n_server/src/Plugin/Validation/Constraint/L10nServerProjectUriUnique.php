<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Plugin\Validation\Constraint;

use Drupal\Core\Validation\Plugin\Validation\Constraint\UniqueFieldConstraint;

/**
 * Checks if the uri of the project is unique.
 *
 * @Constraint(
 *   id = "L10nServerProjectUriUnique",
 *   label = @Translation("Project uri unique", context = "Validation")
 * )
 */
class L10nServerProjectUriUnique extends UniqueFieldConstraint {

  public $message = 'The project uri %value is already taken.';

}
