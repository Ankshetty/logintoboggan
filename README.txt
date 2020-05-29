
*******************************************************
    README.txt for logintoboggan.module for Drupal
*******************************************************

Co-developed by Jeff Robbins (jjeff) and Chad Phillips (hunmonk) with several
features added by Raven Brooks (rbrooks00). The Drupal 8 version retains most
the functionality provided in Drupal 7, although for now it does not support some
contributed sub modules such as Rules, Variable, and Content Access. There is also a
major change in functionality in how it handles a special role for new users. Instead
of removing the authenticated used role until an email validation has been received, it
provides for a new 'trusted' role that gets added once validation is complete.

The Login Toboggan module improves the Drupal login system in an external
module by offering the following features:

   1. Allow users to login using either their username OR their email address.
   2. Allow users to login immediately.
   3. Provide a login form on Access Denied pages for non-logged-in
      (anonymous) users.
   4. The module provides two login block options: One uses JavaScript to
      display the form within the block immediately upon clicking "log in".
      The other brings the user to a separate page, but returns the user to
      their original page upon login.
   5. Customize the registration form with two e-mail fields to ensure
      accuracy.
   6. Optionally redirect the user to a specific page when using the
      'immediate login' feature.
   7. Optionally redirect the user to a specific page upon validation of their
      e-mail address.
   8. Optionally display a user message indicating a successful login.
   9. Optionally combine both the login and registration form on one page.
  10. Optionally display a 'Request new password' link on the user login form.
  11. Optionally have unvalidated users purged from the system at a pre-defined
      interval
      (please read the CAVEATS section of INSTALL.txt for important information
       on configuring this feature!).

Users who choose their own password will automatically be assigned Drupal's authenticated
role. This is core functionality and cannot be reliably altered. So, Logintoboggan now
provides a new 'trusted' role. Once a user validates via email, that role will get applied.
If this feature is used, a site administrator can give authenticated users a limited set of
permissions and can provide a fuller set of permissions to the trusted user. Administrators
should bear in mind that other contrib modules may assume that it's safe to assign permissions
to the authenticated user role. This means there will be an ongoing need to monitor permissions
in order to avoid giving unwanted permissions to a user who has not yet validated email.

