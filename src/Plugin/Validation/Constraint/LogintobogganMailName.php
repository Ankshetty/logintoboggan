<?php

namespace Drupal\logintoboggan\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a name is being used as another account's email.
 *
 * @Constraint(
 *   id = "LogintobogganMailName",
 *   label = @Translation("Unique email for user name required", context = "Validation")
 * )
 */
class LogintobogganMailName extends Constraint {
  public $message = 'You cannot use another user\'s email as a username.';
}

