<?php
require_once dirname(__FILE__) . '/abstract_payment_api_gm.php';

/**
 * GMStone
 * http://www.gmstoneft.com
 *
 * * GM_PAYMENT_API, ID: 159
 *
 * Required Fields:
 * * URL
 * * Account
 * * Extra Info
 *
 * Field Values:
 * * URL: http://www.master-egg.cn/GateWay/ReceiveBank.aspx
 * * Account: ## Merchant ID ##
 * * Extra Info:
 * > {
 * >    "gm_priv_key": ## Private Key ##,
 * >    "gm_pub_key": ## Public Key ##
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_gm extends Abstract_payment_api_gm {

	public function getPlatformCode() {
		return GM_PAYMENT_API;
	}

	public function getPrefix() {
		return 'gm';
	}

    protected function configParams(&$params, $direct_pay_extra_info) {}

	public function getBankType($direct_pay_extra_info) {
		$this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
		if (!empty($direct_pay_extra_info)) {
			$extraInfo = json_decode($direct_pay_extra_info, true);
			if (!empty($extraInfo)) {
				return array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
			}
		} else {
			return parent::getBankType($direct_pay_extra_info);
		}
	}
}
