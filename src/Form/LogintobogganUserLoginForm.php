<?php

/**
 * @file
 * Contains \Drupal\logintoboggan\Form\LogintobogganUserLoginForm.
 */

namespace Drupal\logintoboggan\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Form\UserLoginForm;

/**
 * {@inheritdoc}
 */
class LogintobogganUserLoginForm extends UserLoginForm {
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['email'] = [
      '#type' => 'email',
      '#default_value' => 'email@example.com',
    ];

    return $form;
  }
}