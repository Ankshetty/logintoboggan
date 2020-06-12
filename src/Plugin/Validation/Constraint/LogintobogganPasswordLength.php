<?php

namespace Drupal\logintoboggan\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a name is being used as another account's email.
 *
 * @Constraint(
 *   id = "LogintobogganPasswordLength",
 *   label = @Translation("Check password meets min length setting", context = "Validation")
 * )
 */
class LogintobogganPasswordLength extends Constraint {
  /**
   * Violation message.
   *
   * @var string
   */
  public $message = 'This password does not meet the required length of %length characters.';

}
