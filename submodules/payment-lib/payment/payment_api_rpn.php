<?php
require_once dirname(__FILE__) . '/abstract_payment_api_rpn.php';

/**
 *
 * * RPN_PAYMENT_API, ID: 255
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.rpnsolutions.net/Payapi_Index_Pay.html
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_rpn extends Abstract_payment_api_rpn {

	public function getPlatformCode() {
		return RPN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'rpn';
	}

	public function getBankType($direct_pay_extra_info) {
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
		$params['bank_id'] = $this->getBankType($direct_pay_extra_info);
	}

	# Config in extra_info will overwrite this
	public function getBankListInfoFallback() {
		return array(
			array('label' => '中國工商銀行', 'value' => '1'),
			//array('label' => '中國農業銀行', 'value' => '2'),
			array('label' => '中國銀行', 'value' => '3'),
			array('label' => '中國建設銀行', 'value' => '4'),
			array('label' => '交通銀行', 'value' => '5'),
			array('label' => '中國光大銀行', 'value' => '6'),
			array('label' => '上海浦東發展銀行', 'value' => '7'),
			//array('label' => '北京銀行', 'value' => '8'),
			array('label' => '廣東發展銀行', 'value' => '9'),
			array('label' => '平安銀行', 'value' => '10'),
			array('label' => '興業銀行', 'value' => '11'),
			array('label' => '招商銀行', 'value' => '12'),
			//array('label' => '深圳發展銀行', 'value' => '13'),
			array('label' => '中國郵政儲蓄銀行', 'value' => '14'),
			//array('label' => '華夏銀行', 'value' => '15'),
			//array('label' => '民生銀行', 'value' => '16'),
			array('label' => '中信銀行', 'value' => '17'),
		);
	}
}
