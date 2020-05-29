<?php

/**
 * @file
 * Contains \Drupal\logintoboggan\Access\LogintobogganReValidateAccess.
 */

namespace Drupal\logintoboggan\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface as RoutingAccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use \Drupal\user\Entity\User;

/**
 * Determines access to routes based on login status of current user.
 */
class LogintobogganReValidateAccess implements RoutingAccessInterface {

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('_logintoboggan_revalidate_access');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $fullpath = \Drupal::service('path.current')->getPath();
    $path_parts = explode('/', $fullpath);
    $user = User::load($path_parts[3]);
    return ($account->id() == $user->id() || $account->hasPermission('administer users'))
      ?  AccessResult::allowed()
      :  AccessResult::forbidden();;
  }
}
