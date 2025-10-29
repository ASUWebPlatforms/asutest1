# Drupal Commerce
## Nelnet QuikPAY

### Description

This module allows you to add Nelnet QuikPAY payment gateways to Drupal Commerce.

### Installation
Install the module as [any other Drupal module](http://drupal.org/node/70151).

Once you've installed the QuikPAY module, configure it within the Commerce store configuration menus:

* Administration > Commerce > Configuration > Payment > Payment Gateways

**Choose a redirect method:**
* RTPN Mode
	* **Description:** When a customer clicks on the "proceed to payment" link, QuikPAY opens in a separate window. Once payment is complete, the customer must close that window. QuikPAY will asyncronously notify your Drupal site that a payment has been made, by sending a request to your RTPN page (/quikpay/rtpn). This request is subject to authentication via the keys setup in the payment method configuration, and obtained via Financial Services' Nelnet liaison (more on this below). When the payment completes, the checkout pane will still be open on the Drupal site, but the cart should be cleared. This is a limitation imposed by the QuikPAY RTPN interface.
	* **Note:** Real Time Payment Notifications (RTPN) will need to hit http://yourdomain/quikpay/rtpn (or if you are using SSL, https://yourdomain/quikpay/rtpn) in order to notify your site that a payment has completed. Keep in mind, if using SSL, that self-signed, expired and misconfigured SSL certificates will result in RTPN notifications failing to complete.
* Redirect URL mode (preferred)
	* **Description:** With this mode enabled, when you proceed to the QuikPAY payment site, it does not open in a new window, and when payment completes, you are redirected back to your Drupal site.
	* **Note:** Ask your Financial Services Nelnet liaison to configure the redirectUrl parameter to be the same path you'd have used as the RTPN (the default is https://yourdomain/quikpay/rtpn).

**To determine which modes are available to you and to obtain the proper testing and production servers, please speak with the Financial Services Nelnet liaison.**

The "proceed to checkout" link includes a time-sensitive hash. This means the link will time out. Quikpay only honors hashes with times within 5 minutes of the Quikpay server. This means:
* You want to make sure your server's time is relatively close to your timezone as reported at http://tycho.usno.navy.mil/
* The "proceed to checkout" link times out for users after 5 minutes, give or take. When the user clicks the expired link, QuikPAY will report an error. The user can close that window/tab and return to the original page with the link and refresh the page to get a valid link.

Once your test site is working, request that the production QuikPAY payment processing site be set up and get the keys for it from the Financial Services Nelnet liaison. Please allow 10 days for processing of the final production setup. Enter the production keys and production QuikPAY URL into the QuikPAY payment method settings, and switch to production mode when ready.

**Support for multiple order types**

The QuikPAY module supports multiple Nelnet Quikpay order types. To implement:
1. Setup accounts for each order type with Financial Services, each with a unique order type name string. All accounts should implement identical keys, however, as they will use the same payment method in Drupal as the base-config.
2. Choose one order type to serve as the default fallback order type. Set that as the order type value in the QuikPAY payment gateway config.


### Permissions
Inherited from Commerce. Anonymous will need "access checkout" permission in order to access the RTPN page.

### Pages
* Admin UI for Configuring Payment Method, accessed through store settings:
```/admin/commerce/config/payment-gateways```
* Page used by QuikPAY to report payments:
```/quikpay/rtpn ```

### Credits

This project was originally developed on OSU's GitLab: https://code.osu.edu/osu_commerce/nelnet-quikpay

The QuikPAY module was originally created by:
* Michael Samuelson
* Zohair Zaidi

Additional module development completed by:
* Bryan Roseberry
* Michael Gilardi

OSU implementation and changes:
* Richard Hopkins-Lutz

Drupal 10 Upgrade:
* [Jon Pugh](mailto:jon@thinkdrop.net)

### Support

This module is not actively maintained. If you need any changes or need support implementing it, contact [Jon Pugh](mailto:jon@thinkdrop.net).
