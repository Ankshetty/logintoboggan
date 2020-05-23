<?php
/**
 * @file
 * Contains \Drupal\logintoboggan\Form\LogintobogganRegister.
 */

namespace Drupal\logintoboggan\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\logintoboggan\Utility\LogintobogganUtility;
use Drupal\user\Entity\User;
use Drupal\user\RegisterForm;
use Drupal\Core\Url;


class LoginToBogganRegister extends RegisterForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $user = $this->currentUser();
    /** @var \Drupal\user\UserInterface $account */
    $account = $this->entity;
    $admin = $user->hasPermission('administer users');
    // Pass access information to the submit handler. Running an access check
    // inside the submit function interferes with form processing and breaks
    // hook_form_alter().
    $form['administer_users'] = array(
      '#type' => 'value',
      '#value' => $admin,
    );

    $form['#attached']['library'][] = 'core/drupal.form';



    // For non-admin users, populate the form fields using data from the
    // browser.
    if (!$admin) {
      $form['#attributes']['data-user-info-from-browser'] = TRUE;
    }

    // Because the user status has security implications, users are blocked by
    // default when created programmatically and need to be actively activated
    // if needed. When administrators create users from the user interface,
    // however, we assume that they should be created as activated by default.
    if ($admin) {
      $account->activate();
    }

    // Start with the default user account fields.
    $form = parent::form($form, $form_state, $account);

    //check setting for email confirmation
    $mail_confirm =\Drupal::config('logintoboggan.settings')->get('confirm_email_at_registration');

    //Display a confirm e-mail address box if option is enabled.
    if ($mail_confirm) {
      $form['account']['conf_mail'] = array(
        '#type' => 'textfield',
        '#title' => t('Confirm e-mail address'),
        '#weight' => -28,
        '#maxlength' => 64,
        '#description' => t('Please re-type your e-mail address to confirm it is accurate.'),
        '#required' => TRUE,
      );

      // Weight things properly so that the order is name, mail, conf_mail.
      $form['account']['name']['#weight'] = -30;
      $form['account']['mail']['#weight'] = -29;
    }

    $pass = \Drupal::config('user.settings')->get('user_email_verification');
    $min_pass = \Drupal::config('logintoboggan.settings')->get('minimum_password_length', 0);
    if ($pass && $min_pass > 0) {
      $form['account']['pass']['#description'] = isset($form['account']['pass']['#description']) ? $form['account']['pass']['#description'] . " " : "";
      $form['account']['pass']['#description'] .= t('Password must be at least %length characters.', array('%length' => $min_pass));
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);
    $element['submit']['#value'] = $this->t('Create new account');
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::logger('logintoboggan')->notice('submitForm handler is being called in LoginToBogganRegister');

    $admin = $form_state->getValue('administer_users');
    $reg_pass_set = !\Drupal::config('user.settings')->get('verify_mail', TRUE);

    if (!\Drupal::config('user.settings')->get('verify_mail') || $admin) {
      $pass = $form_state->getValue('pass');
      if(\Drupal::config('logintoboggan.settings')->get('immediate_login_on_register')) {
        $form_state->setValue('status', 1);
      }
    }
    else {
      $pass = user_password();
    }


    //stctodo-role: we don't need this because by default the user does not get a role unless admin manually assigns it
    //or user validates.
    $validating_id = LogintobogganUtility::trustedRole();
    $roles = ($form_state->hasValue('roles') ? array_filter($form_state->getValue('roles')) : array());
    // TODO Reduce number of calls to entityTypeManager()
    $validating_role = \Drupal::entityTypeManager()->getStorage('user_role')->load($validating_id);
    $authenticated_role = \Drupal::entityTypeManager()->getStorage('user_role')->load(\Drupal\user\RoleInterface::AUTHENTICATED_ID);
    if (!\Drupal::config('user.settings')->get('verify_mail', TRUE) && ($validating_role->getWeight() > $authenticated_role->getWeight())) {
      $roles[$validating_role->id()] = $validating_role->id();
    }
    //$form_state->setValue('roles', $roles);

    // Remove unneeded values.
    $form_state->cleanValues();

    $form_state->setValue('pass', $pass);
    $form_state->setValue('init', $form_state->getValue('mail'));

    parent::submitForm($form, $form_state);
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    //Check to see whether our e-mail address matches the confirm address if enabled.
    if ( \Drupal::config('logintoboggan.settings')->get('confirm_email_at_registration') && $form_state->hasValue('conf_mail')) {
      if ($form_state->getValue('mail') != $form_state->getValue('conf_mail')) {
        $form_state->setErrorByName('conf_mail', t('Your e-mail address and confirmed e-mail address must match.'));
      }
    }
  }


  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $account = $this->entity;
    $admin = $form_state->getValue('administer_users');
    $notify = !$form_state->isValueEmpty('notify');

    // Save has no return value so this cannot be tested.
    // Assume save has gone through correctly.
    $account->save();

    $form_state->set('user', $account);
    $form_state->setValue('uid', $account->id());

    $this->logger('user')->notice('New user: %name %email.', array('%name' => $form_state->getValue('name'), '%email' => '<' . $form_state->getValue('mail') . '>', 'type' => $account->link($this->t('Edit'), 'edit-form')));


    // New administrative account without notification.
    $uid = $account->id();
    if ($admin && !$notify) {
      $this->messenger()->addStatus($this->t('Created a new user account for <a href=":url">%name</a>. No email has been sent.', [':url' => $account->toUrl()->toString(), '%name' => $account->getAccountName()]));
    }
    // No email verification required; log in user immediately.
    elseif (!$admin && !\Drupal::config('user.settings')->get('verify_mail') && $account->isActive()) {
      user_login_finalize($account);
      //notify after login so that we get hash based on last login
      _user_mail_notify('register_no_approval_required', $account);

      //stctodo - should only send this message if haven't got a setting to set message
      \Drupal::messenger()->addStatus(t('Registration successful.'));


      $redirect_setting = \Drupal::config('logintoboggan.settings')->get('redirect_on_register');

      $redirect_on_register = !empty($redirect_setting) ? $redirect_setting : '/';

      $redirect = _logintoboggan_process_redirect($redirect_on_register, $account);

      $form_state->setRedirectUrl($redirect);

    }
    // No administrator approval required.
    elseif ($account->isActive() || $notify) {
      if (!$account->getEmail() && $notify) {
        drupal_set_message($this->t('The new user <a href=":url">%name</a> was created without an email address, so no welcome message was sent.', array(':url' => $account->url(), '%name' => $account->getUsername())));
      }
      else {
        $op = $notify ? 'register_admin_created' : 'register_no_approval_required';
        if (_user_mail_notify($op, $account)) {
          if ($notify) {
            $this->messenger()->addMessage(t('A welcome message with further instructions
             has been emailed to the new user <a href=":url">%name</a>.',
              [':url' => $account->url(), '%name' => $account->get('name')]));
          }
          else {
            $this->messenger()->addMessage($this->t('A welcome message with further instructions has been sent to your email address.'));//drupal_set_message($this->t('A welcome message with further instructions has been sent to your email address.'));
            $form_state->setRedirect('<front>');
          }
        }
      }
    }
    // Administrator approval required.
    else {
      _user_mail_notify('register_pending_approval', $account);
      $this->messenger()->addMessage(t('Thank you for applying for an account. Your account
        is currently pending approval by the site administrator..<br />In the meantime, 
        a welcome message with further instructions has been sent to your email address.'));
      $form_state->setRedirect('<front>');
    }
  }

}
