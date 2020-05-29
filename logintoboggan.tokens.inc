<?php

/**
 * @file
 * Builds placeholder replacement tokens for logintoboggan-related data.
 */

use Drupal\logintoboggan\Utility\LogintobogganUtility;

/**
 * Implements hook_token_info().
 */
function logintoboggan_token_info() {
  $info['tokens']['user']['validate-url'] = array(
    'name' => t('Validate URL'),
    'description' => t('The URL of the account validation page, provided by logintoboggan.'),
  );
  return $info;
}

/**
 * Implements hook_tokens().
 */
function logintoboggan_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $replacements = array();

  $url_options = array('absolute' => TRUE);
  if (isset($options['language'])) {
    $url_options['language'] = $options['language'];
  }

  if ($type == 'user' && !empty($data['user'])) {
    $account = $data['user'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Validating URL.
        case 'validate-url':
          $register = Drupal::config('user.settings')->get('register');
          if ($register == 'visitors') {
            $replacements[$original] = LogintobogganUtility::emlValidateUrl($account, $url_options);
          }
          break;
      }
    }
  }

  return $replacements;
}
