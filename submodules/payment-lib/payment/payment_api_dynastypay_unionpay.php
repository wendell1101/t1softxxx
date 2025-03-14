<?php
require_once dirname(__FILE__) . '/abstract_payment_api_dynastypay.php';

/**
 *  DYNASTYPAY 
 *
 *
 * * DYNASTYPAY_UNIONPAY_PAYMENT_API, ID: 5493
 *
 * Required Fields:
 *
 * * URL
 * * Account - Merchant ID
 *
 * Field Values:
 *
 * * Extra Info:
 * > {
 * >    "HashKey" : "## HashKey ##",
 * >	   "HashIV" : "## HashIV ##"
 * > }
 *http://www.dynastypays.com/UnionPayment.php
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_dynastypay_unionpay extends Abstract_payment_api_dynastypay
{
     public function getPlatformCode()
     {
          return DYNASTYPAY_UNIONPAY_PAYMENT_API;
     }
     public function getPrefix()
     {
          return 'dynastypay_unionpay';
     }

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['PayTypr'] = self::PAYTYPE_UNIONPAY;
    }

     # Hide bank selection drop-down
     public function getPlayerInputInfo()
     {
          return array(array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'));
     }

     protected function processPaymentUrlForm($params)
     {
          return $this->processPaymentUrlFormPost($params);
     }
}
