<?php
require_once dirname(__FILE__) . '/abstract_payment_api_yantongyifu.php';

/** 
 *
 * yantongyifu 易付
 * 
 * 
 * * 'YANTONGYIFU_ALIPAY_H5_PAYMENT_API', ID 5068
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL:http:// 212.64.89.203:8889/tran/cashier/pay.ac
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_yantongyifu_alipay_h5 extends Abstract_payment_api_yantongyifu {

	public function getPlatformCode() {
		return YANTONGYIFU_ALIPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'yantongyifu_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['tranType'] = self::TRANTYPE_ALIPAY_H5;
	}

	protected function processPaymentUrlForm($params) {
		if($this->getSystemInfo('use_echo_html')){
			return $this->processPaymentUrlEchoHtml($params);
		}else{
			return $this->processPaymentUrlFormQRCode($params);
		}
	}

	public function getPlayerInputInfo() {
        return array(
             array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }
}
