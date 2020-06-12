<?php

namespace Drupal\logintoboggan\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface as RoutingAccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\Routing\Route;

/**
 * Determines access to routes based on login status of current user.
 */
class LogintobogganReValidateAccess implements RoutingAccessInterface {

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return ['_logintoboggan_revalidate_access'];
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    $fullpath = Url::fromRoute('<current>')->toString();
    $path_parts = explode('/', $fullpath);
    $user_id = $path_parts[3];
    return ($account->id() == $user_id || $account->hasPermission('administer users'))
      ? AccessResult::allowed()
      : AccessResult::forbidden();;
  }

}
