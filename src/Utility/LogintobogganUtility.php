<?php

namespace Drupal\logintoboggan\Utility;


/**
 * Class Utility
 *
 * @package Drupal\logintoboggan\Utility
 */
class LogintobogganUtility {

  /**
   * @return array|mixed|null
   */
  public static function preAuthRole() {
    return \Drupal::config('logintoboggan.settings')->get('pre_auth_role');
  }

  /**
   * @return array|mixed|null
   */
  public static function trustedRole() {
    return \Drupal::config('logintoboggan.settings')->get('trusted_role');
  }
}