<?php

namespace Drupal\logintoboggan\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * validates length of password
 */
class LogintobogganPasswordLengthValidator extends ConstraintValidator {

  public function validate($value, Constraint $constraint) {
    $gotpass = $value->getValue();
    if (!empty($gotpass)) {
      $pass = $value->get(0)->value;
      $min_pass_length = \Drupal::config('logintoboggan.settings')
        ->get('minimum_password_length');
      if ((strlen($pass) > 0) && (strlen($pass) < $min_pass_length)) {
        $this->context->addViolation($constraint->message, ['%length' => $min_pass_length]);
      }
    }
  }

}