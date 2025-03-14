<?php
require_once dirname(__FILE__) . '/abstract_payment_api_txwpay.php';
/**
 * txwpay  同兴旺
 * 
 *
 * TXWPAY_ALIPAY_H5_PAYMENT_API, ID: 5283
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http:// 27.124.8.30/Pay/GateWayAliPay.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_txwpay_alipay_h5 extends Abstract_payment_api_txwpay {

	public function getPlatformCode() {
		return TXWPAY_ALIPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'txwpay_alipay_h5';
    }
    
  

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['pay_type'] = self::PAYTYPE_H5WAP;
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
		
			return $this->processPaymentUrlFormQRCode($params);
		
	}

}
