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
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
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

    $trusted_role = LogintobogganUtility::trustedRole();
    $authenticated_role = \Drupal\User\UserInterface::AUTHENTICATED_ROLE;

    //stctodo - it's possible that the site doesn't bother with the trusted role
    //so this immediate login logic is maybe wrong. What I need to know next is whether there's a flag on the
    //account provided by core that indicates account is not authorised. Otherwise, if no trusted role
    //is provided we don't know whether validation happened. Also, what happens if you login with an
    //email and then just change it.
    $immediate_login = !\Drupal::config('user.settings')->get('verify_mail')
              && $trusted_role != $authenticated_role;



    // No time out for first time login
    // This conditional checks that:
    // - the user does not have the trusted role
    // - the hashed password is correct.
    if (((\Drupal::config('user.settings')->get('verify_mail')
      && !$account->getLastLoginTime()) || ($immediate_login && !$account->hasRole($trusted_role)))
      && $hashed_pass == logintoboggan_eml_rehash($account, $timestamp)) {


      \Drupal::logger('user')->notice('E-mail validation URL used for %name with timestamp @timestamp.',
        ['%name' => $account->getAccountName(), '@timestamp' => $timestamp]);

      $hash =  logintoboggan_eml_rehash($account, $timestamp);

      _logintoboggan_process_validation($account);

      // Where do we redirect after confirming the account
      $redirect_setting = \Drupal::config('logintoboggan.settings')->get('redirect_on_register');
      $redirect_on_register = !empty($redirect_setting) ? $redirect_setting : '/';
      $redirect = _logintoboggan_process_redirect($redirect_on_register, $account);


      switch ($operation) {
        // Proceed with normal user login, as long as it's open registration and their
        // account hasn't been blocked.
        case 'login':
          // Only show the validated message if there's a valid trusted role.
          if ($immediate_login) {
            drupal_set_message(t('You have successfully validated your e-mail address.'));
          }
          if ($account->isBlocked()) {
            drupal_set_message(t('Your account is currently blocked -- login cancelled.'), 'error');
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

          drupal_set_message(t('You have successfully validated %user.', array(
            '%user' => $account->getUsername(),
          )));
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
          drupal_set_message(t('You have successfully validated %user.', array(
            '%user' => $account->getUsername(),
          )));
          //return $this->redirect('<front');
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
      drupal_set_message($message, 'error');
      return new RedirectResponse(\Drupal::url($goto));
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
      drupal_set_message(t("A validation e-mail has been sent to the user's e-mail address."));
    }
    else {
      drupal_set_message(t('A validation e-mail has been sent to your e-mail address. You will need to follow the instructions in that message in order to gain full access to the site.'));
    }

    return new RedirectResponse(\Drupal::url('entity.user.edit_form', array('user' => $user)));
  }

  /**
   * This will return the output of the page.
   */
  public function logintobogganDenied() {
    $account = \Drupal::currentUser();

    if ($account->isAnonymous()) {
      // Output the user login form.
      //$page = logintoboggan_get_authentication_form('login');
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
