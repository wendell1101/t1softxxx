<?php
require_once dirname(__FILE__) . '/abstract_payment_api_juhe.php';

/**
 *
 * JUHE  聚合支付 支付寶
 *
 *
 * * 'JUHE_ALIPAY_PAYMENT_API', ID 5013
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://59.188.235.102:6442/zwsf-posp-proxy/LLyWxAliPayController.app
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_juhe_alipay extends Abstract_payment_api_juhe {

	public function getPlatformCode() {
		return JUHE_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'juhe_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['pay_code'] = self::SCANTYPE_ALIPAY;
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
