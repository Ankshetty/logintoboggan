<?php

/**
 * @file
 * Contains \Drupal\logintoboggan\Form\LogintobogganProfileForm.
 */

namespace Drupal\logintoboggan\Form;


use Drupal\Core\Form\FormStateInterface;
use Drupal\user\ProfileForm;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Session\AccountInterface;
use Drupal\logintoboggan\Utility\LogintobogganUtility;



class LogintobogganProfileForm extends ProfileForm {



  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);

    //add a revalidate link for users who are not yet in the trusted role
    $trusted_role = LogintobogganUtility::trustedRole();
    $authenticated_role = \Drupal\User\UserInterface::AUTHENTICATED_ROLE;
    //provided these two roles are not the same, and the current user does not
    //have the trusted role, can assume user has not yet validated so provide a
    //revalidate link

    $user_account = $form['account'];
    $user_name = $user_account['name']['#default_value'];
    $account = user_load_by_name($user_name);
    $settings = \Drupal::config('logintoboggan.settings');
    //If true this means user can set own password so may not need admin approval to register
    $email_verification = $settings->get('user_email_verification');
    $roles = $account->getRoles();
    $got_trusted = in_array($trusted_role, $roles);
    $currentuser = \Drupal::currentUser();

    //if there's a trusted role, and it's not standard authenticated and user does not have that role
    if (isset($trusted_role) && $trusted_role != $authenticated_role && !$got_trusted) {
      //check the viewer of the page is either the account holder or admin
      if ($currentuser->id() == $account->id() || $currentuser->hasPermission('administer users')) {
        $url = Url::fromRoute('logintoboggan.user_revalidate', ['user' => $account->id()] );
        $validate_link = Link::fromTextAndUrl(t('re-send validation email'), $url)->toString();
        $form['revalidate'] = [
          '#type' => 'fieldset',
          '#title' => t('Account validation'),
          '#weight' => -20,
          '#description' => t('Validate your email to get full access to the site'),
        ];
        $form['revalidate']['revalidate_link'] = [
          '#markup' => $validate_link,
        ];
        $form['account']['roles']['#description'] = t("The user is not assigned LoginToboggan's trusted role, 
        so is currently only receiving authenticated user permissions.");
        $reg_type = \Drupal::config('user.settings')->get('register');
        if ($reg_type == 'visitors_admin_approval') {
          $form['account']['status']['#description'] = t('If this user was created using immediate login, remember to add the %trusted role when activating', ['%trusted' => $trusted_role]);
        }
      }
    }

    //let user know the password length
    $min_pass_length = \Drupal::config('logintoboggan.settings')->get('minimum_password_length');
    if($min_pass_length != '0') {
      $description = $form['account']['pass']['#description'];
      $original = $description->render();
      $pass_message = $original . t('<br>The minimum length for the password is %min characters.', ['%min' => $min_pass_length]);
      $form['account']['pass']['#description'] = $pass_message;
    }


    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $trusted_role = LogintobogganUtility::trustedRole();
    $account = $form_state->getFormObject()->getEntity();
    $trusted = $account->hasRole($trusted_role)?'1':'0';
    $form_state->addBuildInfo('logintoboggan_trusted', $trusted);

    parent::validateForm($form, $form_state);
  }

  public function save(array $form, FormStateInterface $form_state) {
    $currentuser = \Drupal::currentUser();
    if($currentuser->hasPermission('administer users')) {
      $build = $form_state->getBuildInfo();
      $trusted = $build['logintoboggan_trusted'];
      $trusted_role = LogintobogganUtility::trustedRole();
      $account = $form_state->getFormObject()->getEntity();
      $new_roles = $form_state->getValue('roles');
      $role_assigned = in_array($trusted_role, $new_roles);
      if (in_array($trusted_role, $new_roles) && $trusted == '0') {
        _user_mail_notify('status_activated', $account);
      }

    }
    parent::save($form, $form_state);
  }

}
