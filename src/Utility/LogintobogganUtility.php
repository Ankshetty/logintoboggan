<?php

namespace Drupal\logintoboggan\Utility;


use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Class Utility
 *
 * @package Drupal\logintoboggan\Utility
 */
class LogintobogganUtility {


  /**
   * Returns the trusted role setting
   *
   * @return array|mixed|null
   */
  public static function trustedRole() {
    return \Drupal::config('logintoboggan.settings')->get('trusted_role');
  }


  /**
   * Returns a redirect URL
   *
   * @param $redirect
   * @param $account
   *
   * @return \Drupal\Core\Url
   */
  public static function processRedirect($redirect, $account) {
    $variables = array('%uid' => $account->id());
    $redirect = parse_url(urldecode(strtr($redirect, $variables)));
    $redirect = UrlHelper::parse($redirect['path']);
    // If there's a path set, override the destination parameter if necessary.
    if ($redirect['path'] && \Drupal::config('logintoboggan.settings')->get('override_destination_parameter')) {
      \Drupal::request()->query->remove('destination');
    }
    // Explicitly create query and fragment elements if not present already.
    $query = isset($redirect['query']) ? $redirect['query'] : array();
    $fragment = isset($redirect['fragment']) ? $redirect['fragment'] : '';

    return Url::fromUserInput($redirect['path'], ['query' => $query, 'fragment' => $fragment]);
  }

  /**
   * Generates a url for an email token
   *
   * @param $account
   * @param $url_options
   *
   * @return \Drupal\Core\GeneratedUrl|string
   */
  public static function emlValidateUrl($account, $url_options) {
    $request_time = \Drupal::time()->getRequestTime();
    return Url::fromUserInput('/user/validate/' . $account->id() . '/'
      . $request_time . '/' . user_pass_rehash($account, $request_time), $url_options)->toString();
  }

  /**
   *
   * Add trusted role to new user when validating from an email link.
   *
   * @param $account
   *
   */
  public static function processValidation($account) {
    $trusted_role = self::trustedRole();
    //core mail verification not required and trusted <> authenticated so add the role
    $trusted_used = !\Drupal::config('user.settings')->get('verify_mail') && $trusted_role != AccountInterface::AUTHENTICATED_ROLE;
    if (!$account->isBlocked()) {
      if ($trusted_used) {
        $account->addRole($trusted_role);
        $account->save();
      }
    }
  }

}