CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Recommended modules
 * Installation
 * Configuration
 * Troubleshooting
 * Maintainers

INTRODUCTION
------------
This module enables drupal sites to collect payments via paypal, it's lightweight and doesn't use carts.


RECOMMENDED MODULES
------------
hook_event_dispatcher @ https://www.drupal.org/project/hook_event_dispatcher


INSTALLATION
------------
Since this is not yet an official drupal module, the easiest way to install it would be to download this code into your
/modules folder and after install paypal php sdk (`composer require paypal/rest-api-sdk-php:*`) as the module
 extends that library.`https://github.com/paypal/PayPal-PHP-SDK`.
 Make sure you also have hook_event_dispatcher module installed.


CONFIGURATION
------------
After enabling the module go to `http://<example.com>/admin/config/paypal_payments/settings ` and set your paypal
credentials as described here `https://developer.paypal.com/developer/applications/`(My Apps & Credentials).
Finally add a paypal field item to the content type you would wish to collect payment on in admin/structure content types

TROUBLESHOOTING
------------
No known problems yet if you followed the installation instructions above, however if your currency is missing
in the settings form, you can edit `Drupal\paypal_payments\Form\PayPalPaymentsSettingsForm.php` on line 57 or 
suggest an edit.

This module is still a work in progress

MAINTAINERS
------------
Nicholas Njogu Babu, Email nijoba33@gmail.com
