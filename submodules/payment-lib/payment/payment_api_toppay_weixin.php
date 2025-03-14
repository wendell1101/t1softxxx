<?php
require_once dirname(__FILE__) . '/abstract_payment_api_toppay.php';
/**
 * TOPPAY
 *
 * * TOPPAY_WEIXIN_PAYMENT_API, ID: 5608
 *
 * Required Fields:
 *
 * * URL
 * * Account - ## Merchant ID ##
 * * Key - ## API Key ##
 *
 * Field Values:
 *
 * * URL: http://api.toppay168.com/Pay_Index.html
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_toppay_weixin extends Abstract_payment_api_toppay
{
     public function getPlatformCode()
     {
          return TOPPAY_WEIXIN_PAYMENT_API;
     }

     public function getPrefix()
     {
          return 'toppay_weixin';
     }

     protected function configParams(&$params, $direct_pay_extra_info)
     {
          $params['pay_bankcode'] = self::BANKCODE_WEIXIN;
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
