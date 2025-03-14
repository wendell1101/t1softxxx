<?php
require_once dirname(__FILE__) . '/abstract_payment_api_vicus.php';

/**
 *
 * * VICUS_PAYMENT_API, ID: 255
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.vicussolutions.net/Payapi_Index_Pay.html
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_vicus extends Abstract_payment_api_vicus {

	public function getPlatformCode() {
		return VICUS_PAYMENT_API;
	}

	public function getPrefix() {
		return 'vicus';
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

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['Vicus_Paytype'] = self::PAYTYPE_ONLINE_BANK;
	}

	# Config in extra_info will overwrite this
	public function getBankListInfoFallback() {
		return array(
			array('label' => '招商银行', 'value' => 'zsyh'),
			array('label' => '工商银行', 'value' => 'gsyh'),
			array('label' => '建设银行', 'value' => 'jsyh'),
			array('label' => '上海浦发银行', 'value' => 'shpdfzyh'),
			array('label' => '农业银行', 'value' => 'nyyh'),
			array('label' => '民生银行', 'value' => 'msyh'),
			array('label' => '兴业银行', 'value' => 'xyyh'),
			array('label' => '交通银行', 'value' => 'jtyh'),
			array('label' => '光大银行', 'value' => 'gdyh'),
			array('label' => '中国银行', 'value' => 'zgyh'),
			array('label' => '平安银行', 'value' => 'payh'),
			array('label' => '广发银行', 'value' => 'gfyh'),
			array('label' => '中信银行', 'value' => 'zxyh'),
			array('label' => '中国邮政储蓄银行', 'value' => 'zgyzcxyh')
		);
	}
}
