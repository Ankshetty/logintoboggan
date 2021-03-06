<?php

namespace Drupal\logintoboggan\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure search settings for this site.
 */
class LogintobogganHelpForm extends ConfigFormBase {

  /**
   * Module holder variable.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * LogintobogganHelpForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Gets config in scope as dependency.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   Gets module handler in scope.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandler $module_handler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
  }

  /**
   * Inject config and module services.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'logintoboggan_example_help';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'logintoboggan.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $example = $this->t('
      [user:name],

      Thank you for registering.

      IMPORTANT:
      For full site access, you will need to click on this link or copy and paste it in your browser:

      [user:validate-url]

      This will verify your account and log you into the site. In the future you will be able to log in to [site:login-url] using the username and password that you created during registration.
    ');
    $form['foo'] = [
      '#type' => 'textarea',
      '#default_value' => $example,
      '#rows' => 15,
    ];

    return $form;
  }

}
