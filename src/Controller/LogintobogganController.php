<?php
/**
 * @file
 * Contains \Drupal\logintoboggan\Controller\LogintobogganController.
 */

namespace Drupal\logintoboggan\Controller;

use Drupal\logintoboggan\Utility\LogintobogganUtility;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Controller\ControllerBase;
use \Drupal\user\Entity\User;
use Drupal\Core\Url;


class LogintobogganController extends ControllerBase {

  public static function create(ContainerInterface $container) {
    return new static($container->get('module_handler'));
  }

  /**
   * This will return the output of the page.
   */
  public function logintobogganValidateEmail($user, $timestamp, $hashed_pass, $operation) {
    $account = user::load($user);
    $cur_account = \Drupal::currentUser();

    //stctodo - it's possible that the site doesn't bother with the trusted role
    //so this immediate login logic is maybe wrong. What I need to know next is whether there's a flag on the
    //account provided by core that indicates account is not authorised. Otherwise, if no trusted role
    //is provided we don't know whether validation happened. Also, what happens if you login with an
    //email and then just change it.
    $immediate_login = !\Drupal::config('user.settings')->get('verify_mail');


    // No time out for first time login
    // This conditional checks that:
    // - the user can login without verifying email first
    // - the hashed password is correct.
    if (((\Drupal::config('user.settings')->get('verify_mail')
      && !$account->getLastLoginTime()) || ($immediate_login))
      && $hashed_pass == user_pass_rehash($account, $timestamp)) {

      \Drupal::logger('user')->notice('E-mail validation URL used for %name with timestamp @timestamp.',
        ['%name' => $account->getAccountName(), '@timestamp' => $timestamp]);

      //Set trusted role
      LogintobogganUtility::processValidation($account);

      // Where do we redirect after confirming the account
      $redirect_setting = \Drupal::config('logintoboggan.settings')->get('redirect_on_confirm');
      $redirect_on_register = !empty($redirect_setting) ? $redirect_setting : '/';
      $redirect = LogintobogganUtility::processRedirect($redirect_on_register, $account);

      switch ($operation) {
        // Proceed with normal user login, as long as it's open registration and their
        // account hasn't been blocked.
        case 'login':
          // Only show the validated message if there's a valid trusted role.
          if ($immediate_login) {
            \Drupal::messenger()->addMessage(t('You have successfully validated your e-mail address.'), 'status');
          }
          if ($account->isBlocked()) {
            \Drupal::messenger()->addMessage(t('Your account is currently blocked -- login cancelled.'), 'error');

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
            // Mail the user, letting them know their account now has auth user perms.
            _user_mail_notify('status_activated', $account);
          }

          \Drupal::messenger()->addMessage(t('You have successfully validated %user.',
            ['%user' => $account->getUsername()]));
          if ($cur_account->isAnonymous()) {
            return new RedirectResponse(Url::fromRoute('<front>',
              ['user' => $user])->toString());
          } else {
            return new RedirectResponse(Url::fromRoute('entity.user.edit_form',
              ['user' => $user])->toString());
          }
          break;

        // Catch all.
        default:
          \Drupal::messenger()->addMessage(t('You have successfully validated %user.', [
            '%user' => $account->getUsername(),
          ]));
          return new RedirectResponse(Url::fromRoute('<front>')->toString());
          break;
      }
    }
    else {
      $message = t('Sorry, you can only use your validation link once for security reasons.');
      // No one currently logged in, go straight to user login page.
      if ($cur_account->isAnonymous()) {
        $message .= t('Please log in with your username and password instead now.');
        $goto = 'user.login';
      }
      else {
        $goto = 'user.page';
      }
      \Drupal::messenger()->addMessage($message, 'error');
      return new RedirectResponse(Url::fromRoute($goto)->toString());

    }
  }

  /**
   * This will return the output of the page.
   */
  public function logintobogganResendValidation($user) {
    $account = user::load($user);
    /**************************************************************************
    $account->setPassword() = t('If required, you may reset your password from: !url', array(
      '!url' => url('user/password', array('absolute' => TRUE)),
    ));
    /**************************************************************************/
    _user_mail_notify('register_no_approval_required', $account);

    // Notify admin or user that e-mail was sent and return to user edit form.
    if (\Drupal::currentUser()->hasPermission('administer users')) {
      \Drupal::messenger()->addMessage(t("A validation e-mail has been sent to the user's e-mail address."));
    }
    else {
      \Drupal::messenger()->addMessage(t('A validation e-mail has been sent to your e-mail address. You will need to follow the instructions in that message in order to gain full access to the site.'));
    }

    return new RedirectResponse(\Drupal::url('entity.user.edit_form', array('user' => $user)));
  }

  /**
   * This will return the output of the page
   */
  public function logintobogganDenied() {
    $account = \Drupal::currentUser();

    if ($account->isAnonymous()) {
      // Output the user login form.
      $page['#title'] = t('Access Denied / User log in');
    }
    else {
      $page = array(
        '#title'  => t('Access Denied'),
        '#theme' => 'lt_access_denied',
      );
    }

    return $page;
  }
}
