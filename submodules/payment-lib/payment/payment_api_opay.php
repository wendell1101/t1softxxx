<?php
require_once dirname(__FILE__) . '/abstract_payment_api_opay.php';

/**
 * 摇钱树 OPAY
 *
 * * OPAY_PAYMENT_API, ID: 728
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://opay.arsomon.com:28443/vipay/reqctl.do
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_opay extends Abstract_payment_api_opay {

	public function getPlatformCode() {
		return OPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'opay';
	}

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

	# Config in extra_info will overwrite this
	public function getBankListInfoFallback() {
		return array(
			array('label' => '中国银行', 'value' => 'BOC_B2C'),
			array('label' => '中国工商银行', 'value' => 'ICBC_B2C'),
			array('label' => '中国建设银行', 'value' => 'CCB_B2C'),
			array('label' => '中国邮政储蓄银行', 'value' => 'PSBC_B2C'),
			array('label' => '民生银行', 'value' => 'CMBC_B2C'),
			array('label' => '光大银行', 'value' => 'CEB_B2C'),
			array('label' => '北京银行', 'value' => 'BCCB_B2C'),
			array('label' => '中信银行', 'value' => 'ECITIC_B2C'),
			array('label' => '上海银行', 'value' => 'SHB_B2C'),
			array('label' => '北京农商银行', 'value' => 'BJRCB_B2C'),
		);
	}
}
