CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Recommended modules
 * Installation
 * Configuration
 * Troubleshooting
 * Maintainers
 * Known issues

INTRODUCTION
------------
This module enables drupal sites to collect payments via paypal, it's lightweight and doesn't use carts.


INSTALLATION
------------
Since this is not yet an official drupal module, the easiest way to install it would be to download this code into your
/modules folder and after install paypal checkout php sdk <strike>(`composer require paypal/rest-api-sdk-php:*`) </strike> 
(`composer require paypal/paypal-checkout-sdk:*`)
as the module
 extends that library.<strike>`https://github.com/paypal/PayPal-PHP-SDK`</strike>`https://github.com/paypal/Checkout-PHP-SDK`.



CONFIGURATION
------------
After enabling the module go to `http://<example.com>/admin/config/paypal_payments/settings ` and set your paypal
credentials as described here `https://developer.paypal.com/developer/applications/`(My Apps & Credentials).
Finally add a paypal field item to the content type you would wish to collect payment on in admin/structure content types.
NB:: You can also set your app id and secret as environment variables with keys :  "PP_CLIENT_ID" and "PP_CLIENT_SECRET", respectively.

TROUBLESHOOTING
------------
No known problems yet if you followed the installation instructions above, however if your currency is missing
in the settings form, you can edit `Drupal\paypal_payments\Form\PayPalPaymentsSettingsForm.php` on line 57 or 
suggest an edit.

This module is still a work in progress

MAINTAINERS
------------
Nicholas Njogu Babu, Email nijoba33@gmail.com

KNOWN ISSUES
------------
https://github.com/Luehang/react-paypal-button-v2/issues/30