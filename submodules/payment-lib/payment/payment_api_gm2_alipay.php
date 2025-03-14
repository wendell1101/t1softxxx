<?php
require_once dirname(__FILE__) . '/payment_api_gm_alipay.php';

/**
 * GMStone
 * http://www.gmstoneft.com
 *
 * * GM2_ALIPAY_PAYMENT_API, ID: 181
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.master-egg.cn/GateWay/ReceiveBank.aspx
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_gm2_alipay extends Payment_api_gm_alipay {

	public function getPlatformCode() {
		return GM2_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'gm2_alipay';
	}

    protected function configParams(&$params, $direct_pay_extra_info) {}

}
