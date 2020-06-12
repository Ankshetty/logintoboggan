<?php

namespace Drupal\logintoboggan\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'LoginToboggan logged' block.
 *
 * @Block(
 *   id = "logintoboggan_logged_in",
 *   admin_label = @Translation("LoginToboggan logged in block"),
 *   module = "logintoboggan"
 * )
 */
class LoginTobogganloggedBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Variable for current user account.
   *
   * @var account\Drupal\Core\Session\Account
   *   Defines what should be contained in this variable.
   */
  protected $account;

  /**
   * Instantiate class.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Gets dependency injector.
   * @param array $configuration
   *   Gets config in scope.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * Class constructor.
   *
   * @param array $configuration
   *   Config service.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   Current user account.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $account) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->account = $account;
  }

  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */
  public function build() {
    $user = $this->account;
    $page = [
      '#theme' => 'lt_loggedinblock',
      '#account' => $user,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
    return $page;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if ($account->isAnonymous()) {
      return AccessResult::forbidden();
    }
    else {
      return AccessResult::allowed();
    }
  }

}
