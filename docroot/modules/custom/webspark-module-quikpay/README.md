# webspark-module-quikpay

> Implement QuikPay by Nelnet payment gateway with Drupal 8/9 (offsite redirect).

## Installation
[Install](https://github.com/ASUWebPlatforms/webspark-module-quickpay) as a regular Drupal Module.

## Instructions 
1. You must first set up an order type in the QuikPay portal. 
2. The redirect URL defined for the redirect response is "/commerce_nelnet/rtpn". Nelnet should whitelist it (something like "https://yourASUSite.edu/commerce_nelnet/rtpn"). 
3. After installing the module and configuring everything you need in Nelnet, you need to add a new payment gateway. 
4. Select "Redirect QuikPay (Nelnet)". option. 
5. You MUST name the payment gateway as "Quikpay". 
6. The redirect URL defined for the redirect response is "/commerce_nelnet/rtpn". Nelnet should whitelist it (something like "https://yourASUSite.edu/commerce_nelnet/rtpn"). 
7. The values ​​in the payment gateway are recommended, however, if you need customization, you can change those values.
