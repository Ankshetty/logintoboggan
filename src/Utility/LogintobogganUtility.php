<?php

namespace Drupal\logintoboggan\Utility;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Class for utility methods.
 *
 * @package Drupal\logintoboggan\Utility
 */
class LogintobogganUtility {

  /**
   * Returns the trusted role setting.
   *
   * @return array|mixed|null
   *   Role set as trusted.
   */
  public static function trustedRole() {
    return \Drupal::config('logintoboggan.settings')->get('trusted_role');
  }

  /**
   * Returns a redirect URL.
   *
   * @param string $redirect
   *   Redirect location.
   * @param object $account
   *   User account.
   *
   * @return \Drupal\Core\Url
   *   returns a URL object.
   */
  public static function processRedirect($redirect, $account) {
    $variables = ['%uid' => $account->id()];
    $redirect = parse_url(urldecode(strtr($redirect, $variables)));
    $redirect = UrlHelper::parse($redirect['path']);
    // If there's a path set, override the destination parameter if necessary.
    if ($redirect['path'] && \Drupal::config('logintoboggan.settings')->get('override_destination_parameter')) {
      \Drupal::request()->query->remove('destination');
    }
    // Explicitly create query and fragment elements if not present already.
    $query = isset($redirect['query']) ? $redirect['query'] : [];
    $fragment = isset($redirect['fragment']) ? $redirect['fragment'] : '';

    return Url::fromUserInput($redirect['path'], ['query' => $query, 'fragment' => $fragment]);
  }

  /**
   * Generates a url for an email token.
   *
   * @param object $account
   *   User account.
   * @param array $url_options
   *   Any extra URL options.
   */
  public static function emlValidateUrl($account, array $url_options) {
    $request_time = \Drupal::time()->getRequestTime();
    return Url::fromUserInput('/user/validate/' . $account->id() . '/'
      . $request_time . '/' . user_pass_rehash($account, $request_time), $url_options)->toString();
  }

  /**
   * Add trusted role to new user when validating from an email link.
   *
   * @param object $account
   *   User account.
   */
  public static function processValidation($account) {
    $trusted_role = self::trustedRole();
    // Core mail verification not required and trusted <>
    // authenticated so add the role.
    $trusted_used = !\Drupal::config('user.settings')->get('verify_mail') &&
      $trusted_role != AccountInterface::AUTHENTICATED_ROLE;
    if (!$account->isBlocked()) {
      if ($trusted_used) {
        $account->addRole($trusted_role);
        $account->save();
      }
    }
  }

}
