<?php

namespace Drupal\logintoboggan\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'LoginToboggan login' block.
 *
 * @Block(
 *   id = "logintoboggan_log_in",
 *   admin_label = @Translation("LoginToboggan log in block"),
 *   module = "logintoboggan"
 * )
 */
class LogintobogganLoginBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Class constructor.
   *
   * @param array $configuration
   *   Config service.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['login_display_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Block display type'),
      '#options' => [
        $this->t('Standard'),
        $this->t('Link'),
        $this->t('Collapsible form'),
      ],
      '#description' => $this->t("'Standard' is a standard login block, 
      'Link' is a login link that returns the user to the original page after 
      logging in, 'Collapsible form' is a javascript collaspible login form."),
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

    // Default type to zero.
    $block_type = $config['login_display_type'] ?? '0';

    // Build a login form. This copies mostly from core UserLoginBlock.
    if ($block_type == '0' || $block_type == '2') {
      $login_form = $this->formBuilder->getForm('Drupal\user\Form\UserLoginForm');
      unset($login_form['name']['#attributes']['autofocus']);
      // When unsetting field descriptions, unset aria-describedby attributes
      // to avoid introducing an accessibility bug.
      unset($login_form['name']['#description']);
      unset($login_form['name']['#attributes']['aria-describedby']);
      unset($login_form['pass']['#description']);
      unset($login_form['pass']['#attributes']['aria-describedby']);
      $login_form['name']['#size'] = 15;
      $login_form['pass']['#size'] = 15;

      $placeholder = 'form_action_p_4r8ITd22yaUvXM6SzwrF6huIvE248hz9k1Sxto3pBvE';
      $login_form['#attached']['placeholders'][$placeholder] = [
        '#lazy_builder' => ['\Drupal\logintoboggan\Plugin\Block\LogintobogganLoginBlock::renderPlaceholderFormAction', []],
      ];
      $login_form['#action'] = $placeholder;
    }

    $link = [
      '#title' => $this->t('Login in / register'),
      '#type' => 'link',
      '#url' => Url::fromRoute('user.login', ['query' => ['destination' => '/node/100']]),
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
    else {
      return AccessResult::allowed();
    }
  }

  /**
   * Lazy_builder callback; renders a form action URL including destination.
   *
   * @return array
   *   A renderable array representing the form action.
   *
   * @see \Drupal\Core\Form\FormBuilder::renderPlaceholderFormAction()
   */
  public static function renderPlaceholderFormAction() {
    return [
      '#type' => 'markup',
      '#markup' => Url::fromRoute('<current>', [], ['query' => \Drupal::destination()->getAsArray(), 'external' => FALSE])->toString(),
      '#cache' => ['contexts' => ['url.path', 'url.query_args']],
    ];
  }

}
