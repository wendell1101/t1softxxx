<?php
require_once dirname(__FILE__) . '/abstract_payment_api_sdpay.php';
/**
 * SDPAY
 *
 * * SDPAY_UNIONPAY_PAYMENT_API, ID: 5501
 * *
 *
 * Required Fields:
 * * Account
 * * URL
 * * Extra Info
 *
 * Field Values:
 * * Account: ## Merchant ID ##
 * * URL: https://apimt.pr0pay.com/deposit/merchant/{## Merchant ID ##}/transaction
 * * Extra Info:
 * > {
 * >    "sdpay_pub_key": "## Platform Public Key ##",
 * >    "sdpay_priv_key": "## Merchant Private Key ##",
 * >    "use_usd_currency" : true
 * > }
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_sdpay_unionpay extends Abstract_payment_api_sdpay
{
     public function getPlatformCode()
     {
          return SDPAY_UNIONPAY_PAYMENT_API;
     }

     public function getPrefix()
     {
          return 'sdpay_unionpay';
     }

     protected function configParams(&$params, $direct_pay_extra_info)
     {
          $params['bankCode'] = null;
          $params['paymentTypeCode'] = self::UNIONPAY_QRCODE_PAY;
     }

     # Hide bank selection drop-down
     public function getPlayerInputInfo()
     {
          return array(array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'));
     }

     protected function processPaymentUrlForm($params)
     {
          if ($this->CI->utils->is_mobile()) {
               return $this->processPaymentUrlFormRedirect($params);
          } else {
               return $this->processPaymentUrlFormPost($params);
          }
     }
}
