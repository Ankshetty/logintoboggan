(function ($, Drupal) {

  Drupal.behaviors.loginTobogganloginblock = {
    attach: function (context, settings) {

      $('#toboggan-login', context).once('toggleboggan_setup').each( function () {
        $(this).hide();
        Drupal.logintoboggan_toggleboggan();
      });
    }
  };

  Drupal.logintoboggan_toggleboggan = function() {
    $("#toboggan-login-link").click(
      function () {
        $("#toboggan-login").slideToggle("fast");
        $(this).blur();
        return false;
      }
    );
  };

})(jQuery, Drupal);

