(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.unifiedLogin = {

    attach: function (context, settings) {

      var active = drupalSettings.LoginToboggan.unifiedLoginActiveForm;

      // Attach behaviors to the links so that they show/hide forms appropriately.
      $('.toboggan-unified #register-link').click(function() {
        $(this).addClass('lt-active').blur();
        $('.toboggan-unified #login-link').removeClass('lt-active');
        $('.toboggan-unified #password-link').removeClass('lt-active');
        $('.user-login-form').hide();
        $('.user-pass').hide();
        $('.user-register-form').show();
        return false;
      });
      $('.toboggan-unified #login-link').click(function() {

        $(this).addClass('lt-active').blur();
        $('.toboggan-unified #register-link').removeClass('lt-active');
        $('.toboggan-unified #password-link').removeClass('lt-active');
        $('.user-login-form').show();
        $('.user-register-form').hide();
        $('.user-pass').hide();
        return false;
      });

      $('.toboggan-unified #password-link').click(function() {
        $(this).addClass('lt-active').blur();
        $('.toboggan-unified #register-link').removeClass('lt-active');
        $('.toboggan-unified #login-link').removeClass('lt-active');
        $('.user-pass').show();
        $('.user-register-form').hide();
        $('.user-login-form').hide();
        return false;
      });


      switch(drupalSettings.LoginToboggan.unifiedLoginActiveForm) {
        case 'register':
          $('.toboggan-unified #register-link').click();
          break;
        case 'login':
        default:
          $('.toboggan-unified #login-link').click();
          break;
      }

    }

  };

})(jQuery, Drupal, drupalSettings);
