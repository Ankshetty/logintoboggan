<?php

namespace Drupal\logintoboggan\Controller;

use Drupal\logintoboggan\Utility\LogintobogganUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Drupal\Core\Url;

/**
 * Class LogintobogganController.
 *
 * @package Drupal\logintoboggan\Controller
 */
class LogintobogganController extends ControllerBase {

  /**
   * Basic create.
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('module_handler'));
  }

  /**
   * This will return the output of the page.
   */
  public function logintobogganValidateEmail($user, $timestamp, $hashed_pass, $operation) {
    $account = user::load($user);

    // $cur_account = \Drupal::currentUser();
    $cur_account = $this->currentUser();

    // If you don't need to verify email (i.e. can set password), that's
    // effectively ok for immediate login.
    $immediate_login = $this->config('user.settings')->get('verify_mail');
    // Does have to verify but has not logged in previously OR
    // the user can login without verifying email first
    // - the hashed password is correct.
    if ((($this->config('user.settings')->get('verify_mail')
      && !$account->getLastLoginTime()) || ($immediate_login))
      && $hashed_pass == user_pass_rehash($account, $timestamp)) {

      $this->getLogger('user')->notice('E-mail validation URL used for %name with 
      timestamp @timestamp.',
        ['%name' => $account->getAccountName(), '@timestamp' => $timestamp]);

      // Add trusted role.
      LogintobogganUtility::processValidation($account);

      // Where do we redirect after confirming the account?
      $redirect_setting = $this->config('logintoboggan.settings')->get('redirect_on_confirm');
      $redirect_on_register = !empty($redirect_setting) ? $redirect_setting : '/';
      $redirect = LogintobogganUtility::processRedirect($redirect_on_register, $account);

      switch ($operation) {
        // Proceed with normal user login, as long as it's open registration and
        // account hasn't been blocked.
        case 'login':
          // Only show the validated message if there's a valid trusted role.
          if ($immediate_login) {
            $this->messenger()->addMessage($this->t('You have successfully validated your e-mail address.'), 'status');
          }
          if ($account->isBlocked()) {
            $this->messenger()->addMessage($this->t('Your account is currently blocked -- login cancelled.'), 'error');

            return new RedirectResponse(Url::fromRoute('<front>')->toString());

          }
          else {
            $redirect = logintoboggan_process_login($account, $redirect);
            return new RedirectResponse($redirect->toString());
          }
          break;

        // Admin validation.
        case 'admin':
          if ($immediate_login) {
            _user_mail_notify('status_activated', $account);
          }

          $this->messenger()->addMessage($this->t('You have successfully validated %user.',
            ['%user' => $account->getUsername()]));
          if ($cur_account->isAnonymous()) {
            return new RedirectResponse(Url::fromRoute('<front>',
              ['user' => $user])->toString());
          }
          else {
            return new RedirectResponse(Url::fromRoute('entity.user.edit_form',
              ['user' => $user])->toString());
          }
          break;

        // Catch all.
        default:
          $this->messenger()->addMessage($this->t('You have successfully validated %user.', [
            '%user' => $account->getUsername(),
          ]));
          return new RedirectResponse(Url::fromRoute('<front>')->toString());
      }
    }
    else {
      $message = $this->t('Sorry, you can only use your validation link once for security reasons.');
      // No one currently logged in, go straight to user login page.
      if ($cur_account->isAnonymous()) {
        $message .= $this->t('Please log in with your username and password instead now.');
        $goto = 'user.login';
      }
      else {
        $goto = 'user.page';
      }
      $this->messenger()->addMessage($message, 'error');
      return new RedirectResponse(Url::fromRoute($goto)->toString());

    }
  }

  /**
   * This will return the output of the page.
   */
  public function logintobogganResendValidation($user) {
    $account = user::load($user);
    _user_mail_notify('register_no_approval_required', $account);

    // Notify admin or user that e-mail was sent and return to user edit form.
    if ($this->currentUser()->hasPermission('administer users')) {
      $this->messenger()->addMessage($this->t("A validation e-mail has been sent to the user's e-mail address."));
    }
    else {
      $this->messenger()->addMessage($this->t('A validation e-mail has been sent to your e-mail address. You will need to follow the instructions in that message in order to gain full access to the site.'));
    }

    return new RedirectResponse(URL::fromRoute('entity.user.edit_form', ['user' => $user])->toString());
  }

  /**
   * This will return the output of the page.
   */
  public function logintobogganDenied() {
    $account = $this->currentUser();
    if ($account->isAnonymous()) {
      $page['#title'] = $this->t('Access Denied / User log in');
    }
    else {
      $page = [
        '#title'  => $this->t('Access Denied'),
        '#theme' => 'lt_access_denied',
      ];
    }
    return $page;
  }

}
