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


RECOMMENDED MODULES
------------
hook_event_dispatcher @ https://www.drupal.org/project/hook_event_dispatcher


INSTALLATION
------------
Since this is not yet an official drupal module, the easiest way to install it would be to download this code into your
/modules folder and after install paypal php sdk (`composer require paypal/rest-api-sdk-php:*`) as the module
 extends that library.
`https://github.com/paypal/PayPal-PHP-SDK`

CONFIGURATION
------------
After enabling the module go to `http://<example.com>/admin/config/paypal_payments/settings ` and set your paypal
credentials as described here `https://www.paypal.com/us/webapps/mpp/set-up-paypal-business-account`.
Finally add a paypal field item to the content type you would wish to collect payment on in admin/structure content types

TROUBLESHOOTING
------------
This module is still a work in progress

MAINTAINERS
------------
Nicholas Njogu Babu, Email nijoba33@gmail.com
