<?php

namespace Drupal\logintoboggan\Access;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface as RoutingAccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Determines access to routes based on login status of current user.
 */
class LogintobogganValidateAccess implements RoutingAccessInterface {

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Class constructor.
   */
  public function __construct(Time $datetime) {
    $this->time = $datetime;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return ['_logintoboggan_validate_email_access'];
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    $path = Url::fromRoute('<current>')->toString();
    $arg = explode('/', $path);
    $request_time = $this->time->getRequestTime();
    return $arg[4] < $request_time
      ? AccessResult::allowed()
      : AccessResult::forbidden();
  }

}
