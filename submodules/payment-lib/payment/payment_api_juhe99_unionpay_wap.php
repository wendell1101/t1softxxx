<?php
require_once dirname(__FILE__) . '/abstract_payment_api_juhe99.php';

/** 
 *
 * JUHE99  聚合99 銀聯 WAP
 * 
 * 
 * * 'JUHE99_UNIONPAY_WAP_PAYMENT_API', ID 920
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://t.9556182.com/trx-service/appPay/api.action
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_juhe99_unionpay_wap extends Abstract_payment_api_juhe99 {

	public function getPlatformCode() {
		return JUHE99_UNIONPAY_WAP_PAYMENT_API;
	}

	public function getPrefix() {
		return 'juhe99_unionpay_wap';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
  
        	$params['r3_payType'] =  self::PAYTYPE_WAP;
            $params['r7_appPayType'] = self::SCANTYPE_UNIONPAY;
	}

	protected function processPaymentUrlForm($params) {

			return $this->processPaymentUrlFormQRCode($params);
	}

	public function getPlayerInputInfo() {
        
            return array(
                 array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
            );
    }

}
