<?php

// Tested on PHP 5.2, 5.3

// This snippet (and some of the curl code) due to the Facebook SDK.
if (!function_exists('curl_init')) {
  throw new Exception('Stripe needs the CURL PHP extension.');
}
if (!function_exists('json_decode')) {
  throw new Exception('Stripe needs the JSON PHP extension.');
}

// Stripe singleton
require_once(dirname(__FILE__) . '/Stripe/Stripe.php');

// Utilities
require_once(dirname(__FILE__) . '/Stripe/Util.php');
require_once(dirname(__FILE__) . '/Stripe/Util/Set.php');

// Errors
require_once(dirname(__FILE__) . '/Stripe/Error.php');
require_once(dirname(__FILE__) . '/Stripe/ApiError.php');
require_once(dirname(__FILE__) . '/Stripe/ApiConnectionError.php');
require_once(dirname(__FILE__) . '/Stripe/AuthenticationError.php');
require_once(dirname(__FILE__) . '/Stripe/CardError.php');
require_once(dirname(__FILE__) . '/Stripe/InvalidRequestError.php');

// Plumbing
require_once(dirname(__FILE__) . '/Stripe/Object.php');
require_once(dirname(__FILE__) . '/Stripe/ApiRequestor.php');
require_once(dirname(__FILE__) . '/Stripe/ApiResource.php');
require_once(dirname(__FILE__) . '/Stripe/SingletonApiResource.php');
require_once(dirname(__FILE__) . '/Stripe/List.php');

// Stripe API Resources
require_once(dirname(__FILE__) . '/Stripe/Account.php');
require_once(dirname(__FILE__) . '/Stripe/Charge.php');
require_once(dirname(__FILE__) . '/Stripe/Customer.php');
require_once(dirname(__FILE__) . '/Stripe/Invoice.php');
require_once(dirname(__FILE__) . '/Stripe/InvoiceItem.php');
require_once(dirname(__FILE__) . '/Stripe/Plan.php');
require_once(dirname(__FILE__) . '/Stripe/Token.php');
require_once(dirname(__FILE__) . '/Stripe/Coupon.php');
require_once(dirname(__FILE__) . '/Stripe/Event.php');
require_once(dirname(__FILE__) . '/Stripe/Transfer.php');
