<?php
require_once dirname(__FILE__) . '/abstract_payment_api_sdpay.php';
/**
 * SDPAY
 *
 * * SDPAY_BANKCARD_PAYMENT_API, ID: 5500
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
class Payment_api_sdpay_bankcard extends Abstract_payment_api_sdpay
{
     public function getPlatformCode()
     {
          return SDPAY_BANKCARD_PAYMENT_API;
     }

     public function getPrefix()
     {
          return 'sdpay_bankcard';
     }

     protected function configParams(&$params, $direct_pay_extra_info)
     {
          //    if (!empty($direct_pay_extra_info)) {
          //        $extraInfo = json_decode($direct_pay_extra_info, true);
          //        if (!empty($extraInfo)) {
          //            $params['bankCode'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
          //        }
          //    }
          $params['bankCode'] = null;
          $params['paymentTypeCode'] = self::BANK_CARD_PAY;
     }

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
