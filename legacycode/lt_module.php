<?php

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\logintoboggan\Utility\LogintobogganUtility;
use Drupal\user\Entity\User;


/**
 * Custom submit function for user registration form
 *
 * @ingroup logintoboggan_form
 */
function _logintoboggan_user_register_submit($form, FormStateInterface $form_state) {
  $reg_pass_set = !\Drupal::config('user.settings')->get('verify_mail', TRUE);

  $entity = $form_state->getFormObject();
  $account = $entity->getEntity();


  // Test here for a valid pre-auth -- if the pre-auth is set to the auth user, we
  // handle things a bit differently.
  //$pre_auth = logintoboggan_validating_id() != AccountInterface::AUTHENTICATED_ROLE;
  $pre_auth = LogintobogganUtility::trustedRole() != AccountInterface::AUTHENTICATED_ROLE;

  // If we are allowing user selected passwords then skip the auto-generate function
  // The new user's status will be 1 (visitors can create own accounts) if reg_pass_set == 1
  // Immediate login, we are going to assign a pre-auth role, until email validation completed
  if ($reg_pass_set) {
    //$pass = $form_state->getValue('pass');
    //$status = 1;
  }
  else {
    //$pass = user_password();
    //stc the constant user_register_visitors is moved to an interface
    $status = !\Drupal::config('user.settings')->get('user_register') == USER_REGISTER_VISITORS;
  }


  // Set the roles for the new user -- add the pre-auth role if they can pick their own password,
  // and the pre-auth role isn't anon or auth user.
  $validating_id = LogintobogganUtility::trustedRole();
  $roles = ($form_state->hasValue('roles') ? array_filter($form_state->getValue('roles')) : array());
  if ($reg_pass_set && ($validating_id > \Drupal\user\RoleInterface::AUTHENTICATED_ID )) {
    $roles[$validating_id] = 1;
  }

  $form_state->setValue('pass', $pass);
  $form_state->setValue('init', $form_state->getValue('mail'));
  $form_state->setValue('roles', $roles);
  //$form_state->setValue('status', $status);


  // Add plain text password into user account to generate mail tokens.
  $account->password = $pass;

  // Compose the appropriate user message. Validation emails are only sent if:
  //   1. Users can set their own password.
  //   2. The pre-auth role isn't the auth user.
  //   3. Visitors can create their own accounts.
  $message = t('Further instructions have been sent to your e-mail address.');
  $config = \Drupal::config('user.settings');
  if($reg_pass_set && $pre_auth && $config->get('register') == USER_REGISTER_VISITORS) {
    $message = t('A validation e-mail has been sent to your e-mail address. You will need to follow the instructions in that message in order to gain full access to the site.');
  }
  if ($config->get('register') == USER_REGISTER_VISITORS) {

    // Create new user account, no administrator approval required.
    $mailkey = 'register_no_approval_required';

  } elseif ($config->get('register') == USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL) {

    // Create new user account, administrator approval required.
    $mailkey = 'register_pending_approval';

    $message = t('Thank you for applying for an account. Your account is currently pending approval by the site administrator.<br />Once it has been approved, you will receive an e-mail containing further instructions.');
  }

  // Mail the user.
  // - this needs to be done after account has been created because at this point is has no uid and fails
  //_user_mail_notify($mailkey, $account); //@TODO correctly detect the preferred language instead of enforcing 'en'.

  drupal_set_message($message);

  // where do we need to redirect after registration?
  //$acccount has no uid at this point so the redirect function can't work
  $redirect = _logintoboggan_process_redirect(\Drupal::config('logintoboggan.settings')->get('redirect_on_register'), $account);
  // Log the user in if they created the account and immediate login is enabled.
  if($reg_pass_set && \Drupal::config('logintoboggan.settings')->get('immediate_login_on_register')) {
    //$form_state['redirect'] = logintoboggan_process_login($account, $form_state['values'], $redirect);
    $form_state->setRedirectUrl($redirect);
  }
  else {
    // Redirect to the appropriate page.
    $form_state->setRedirectUrl($redirect);
  }
}


/**
 * Implement hook_menu_get_item_alter()
 *
 * @ingroup logintoboggan_core
 *
 * This is the best current place to dynamically remove the authenticated role
 * from the user object on initial page load.  hook_init() is too late, as menu
 * access checks have already been performed.
 */
function _logintoboggan_menu_get_item_alter() {
  $account = \Drupal::currentUser();

  // Make sure any user with pre-auth role doesn't have authenticated user role
  _logintoboggan_user_roles_alter($account);
}

/**
 * Alter user roles for loaded user account.
 *
 * If user is not an anonymous user, and the user has the pre-auth role, and the pre-auth role
 * isn't also the auth role, then unset the auth role for this user--they haven't validated yet.
 *
 * This alteration is required because sess_read() and user_load() automatically set the
 * authenticated user role for all non-anonymous users (see http://drupal.org/node/92361).
 *
 * this is now irrelevant because a role doesn't get applied until validation
 *
 * @param &$account
 *    User account to have roles adjusted.
 */
function _logintoboggan_user_roles_alter($account) {
  $id = LogintobogganUtility::trustedRole();
  // if (!$account->isAnonymous() && $account->hasRole($id)) {
  //  if ($id != DRUPAL_AUTHENTICATED_RID) {
  //    // unset($account->roles[DRUPAL_AUTHENTICATED_RID]);
  //    // // Reset the permissions cache.
  //    // drupal_static_reset('user_access');
  //  }
  // }
}

/**
 * Alter user roles for loaded user account.
 *
 * If user is not an anonymous user, and the user has the pre-auth role, and the pre-auth role
 * isn't also the auth role, then unset the auth role for this user--they haven't validated yet.
 *
 * This alteration is required because sess_read() and user_load() automatically set the
 * authenticated user role for all non-anonymous users (see http://drupal.org/node/92361).
 *
 * this is now irrelevant because a role doesn't get applied until validation
 *
 * @param &$account
 *    User account to have roles adjusted.
 */


/**
 * Implement hook_form_user_register_form_alter().
 *
 *
 * @ingroup logintoboggan_core
 */
function _logintoboggan_form_user_register_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  // Admin created accounts are only validated by the module

  $user = \Drupal::currentUser();
  if ($user->hasPermission('administer users')) {
    return;
  }

  // Ensure a valid submit array.
  $form['#submit'] = is_array($form['#submit']) ? $form['#submit'] : array();

  // Replace core's registration function with LT's registration function.
  // Put the LT submit handler first, so other submit handlers have a valid
  // user to work with upon registration.
  $key = array_search('user_register_submit', $form['#submit']);
  if ($key !== FALSE) {
    unset($form['#submit'][$key]);
  }
  array_unshift($form['#submit'],'logintoboggan_user_register_submit');
  array_unshift($form['actions']['submit']['#submit'], 'logintoboggan_user_register_submit');

}




/**
 *
 * this generates a link to a controller for validating but it has a
 * problem if users is pending approval because user will be blocked so the function
 * that adds the trusted role will not actually apply it. Would need to either have a
 * separate function that can evaluate whether the admin user is logged-in, and if so unblock
 * the user account, or alter the flow of the controller so it knows this is an admin and
 * can therefore set user to active and apply trusted role.
 *
 * Implement hook_mail_alter().
 */
function logintoboggan_mail_alter(&$message) {
  if ($message['id'] == 'user_register_pending_approval_admin') {
    $reg_pass_set = !\Drupal::config('user.settings')->get('verify_mail');
    if ($reg_pass_set) {
      $account = $message['params']['account'];
      $url_options = array('absolute' => TRUE);
      $language = $message['language'];
      $langcode = isset($language->language) ? $language->language : NULL;
      $message['body'][] = t("\n\nTo give the user full site permissions, 
      click the link below:\n\n:validation_url/admin\n\nAlternatively, 
      you may visit their user account listed above 
      and assign the trusted role.", array(':validation_url' => logintoboggan_eml_validate_url($account, $url_options)), array('langcode' => $langcode));
    }
  }
}


/*
 * submit function for pass reset - not used due to change away from pre auth role
 */
function logintoboggan_pass_reset($form, $form_state) {

}

/**
 * @return string
 *
 */
function _logintoboggan_protocol() {
  return ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http');
}


/**
 * hook no longer exists and don't think it's helpful given that we can't know the
 * regions on a page reliably.
 *
 * Implement hook_page_alter().
 */
function logintoboggan_page_alter(&$page) {
  // Remove blocks on access denied pages.
  if (isset($page['#logintoboggan_denied'])) {
    drupal_set_message(t('Access denied. You may need to login below or register to access this page.'), 'error');
    // Allow overriding the removal of the sidebars, since there's no way to
    // override this in the theme.
    if (\Drupal::config('logintoboggan.settings')
      ->get('denied_remove_sidebars', TRUE)) {
      unset($page['sidebar_first'], $page['sidebar_second']);
    }
  }
}

/**
 *
 * Add trusted role to new user when validating from an email link.
 *
 * @param $account
 *
 */
function _logintoboggan_process_validation($account) {
  $trusted_role = LogintobogganUtility::trustedRole();
  //core mail verification not required and trusted <> authenticated so add the role
  $trusted_used = !\Drupal::config('user.settings')->get('verify_mail') && $trusted_role != AccountInterface::AUTHENTICATED_ROLE;
  if (!$account->isBlocked()) {
    if ($trusted_used) {
      $account->addRole($trusted_role);
      $account->save();
    }
  }
}







