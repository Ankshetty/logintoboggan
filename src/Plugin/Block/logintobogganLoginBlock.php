<?php

namespace Drupal\logintoboggan\Plugin\Block;

use Drupal;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\Xss;

/**
 * Provides a 'LoginToboggan login' block.
 *
 * @Block(
 *   id = "logintoboggan_log_in",
 *   admin_label = @Translation("LoginToboggan log in block"),
 *   module = "logintoboggan"
 * )
 */
class logintobogganLoginBlock extends BlockBase {

  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['login_display_type'] = [
      '#type' => 'radios',
      '#title' => t('Block display type'),
      '#options' => array(t('Standard'), t('Link'), t('Collapsible form')),
      '#description' => t("'Standard' is a standard login block, 'Link' is a login link that returns the user to the original page after logging in, 'Collapsible form' is a javascript collaspible login form."),
      '#default_value' => isset($config['login_display_type']) ? $config['login_display_type'] : '0',
    ];

    $form['login_block_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#description' => $this->t('Message to display at top of block'),
      '#default_value' => isset($config['login_block_message']) ? $config['login_block_message'] : '',
    ];

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['login_display_type'] = $values['login_display_type'];
    $this->configuration['login_block_message'] = Xss::filter($values['login_block_message']);
  }


  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */
  public function build() {
    $config = $this->getConfiguration();

    //default type to zero
    $block_type = $config['login_display_type'] ?? '0';

    //build a login form. Note this reproduces various settings from core UserLoginBlock
    if ($block_type == '0' || $block_type == '2') {
      $login_form = Drupal::formBuilder()->getForm(Drupal\user\Form\UserLoginForm::class);
      unset($login_form['name']['#attributes']['autofocus']);
      // When unsetting field descriptions, also unset aria-describedby attributes
      // to avoid introducing an accessibility bug.
      unset($login_form['name']['#description']);
      unset($login_form['name']['#attributes']['aria-describedby']);
      unset($login_form['pass']['#description']);
      unset($login_form['pass']['#attributes']['aria-describedby']);
      $login_form['name']['#size'] = 15;
      $login_form['pass']['#size'] = 15;
    }

    $link = [
      '#title' => t('Login in / register'),
      '#type' => 'link',
      '#url' => Url::fromRoute('user.login'),
      '#attributes' => [
        'id' => 'toboggan-login-link',
      ],
    ];

    if ($block_type == '1') {
      $login_form = $link;
    }

    $message = !empty($config['login_block_message']) ? $config['login_block_message'] : '';

    $build = [];

    $build['#cache']['max-age'] = 0;
    $build['#theme'] = 'lt_login_block';
    $build['content'] = [
      'user_login_form' => $login_form,
      'message' => $message,
      'login_link' => $link,
      'block_type' => $block_type,
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if (!$account->isAnonymous()) {
      return AccessResult::forbidden();
    }
    else{
      return AccessResult::allowed();
    }
  }

}