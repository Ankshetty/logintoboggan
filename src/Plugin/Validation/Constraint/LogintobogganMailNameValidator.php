<?php

namespace Drupal\logintoboggan\Plugin\Validation\Constraint;

use Drupal\Core\Database\Database;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;


/**
 * validates that user name that uses email is not using a pre-existing email
 */
class LogintobogganMailNameValidator extends ConstraintValidator {

  /**
   * Check that user name is not an email used for another account
   *
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    $parent = $items->getEntity();
    $uid = $parent->get('uid')->value;
    $name  = $items->get(0)->value;
    $connection = Database::getConnection();
    if (is_null($uid)) {
      $result = $connection->select('users_field_data', 'ufd')
        ->fields('ufd', ['mail'])
        ->condition('mail', $name, 'LIKE')
        ->countQuery()
        ->execute()
        ->fetchField();
    } else {
      $result = $connection->select('users_field_data', 'ufd')
        ->fields('ufd', ['mail'])
        ->condition('mail', $name, 'LIKE')
        ->condition('uid', $uid, '<>')
        ->countQuery()
        ->execute()
        ->fetchField();
    }

    if($result > 0){
      $this->context->addViolation($constraint->message, []);
  }

  }
}