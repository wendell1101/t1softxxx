<?php
require_once dirname(__FILE__) . '/abstract_payment_api_daddypay.php';

/**
 *
 * DaddyPay wechat 微信
 *
 * DADDYPAY_WECHAT_PAYMENT_API, ID: 131
 *
 * Required Fields:
 * * URL
 * * Key - signing key
 * * Extra Info
 *
 * Field Values:
 *
 * * URL (sandbox): http://52.69.65.224/Mownecum_2_API_Live/Deposit?format=json
 * * Extra Info
 * > {
 * >     "daddypay_company_id" : "## company id ##"
 * >	}
 * > }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_daddypay_wechat extends Abstract_payment_api_daddypay {

	public function getPlatformCode() {
		return DADDYPAY_WECHAT_PAYMENT_API;
	}

	public function getPrefix() {
		return 'daddypay_wechat';
	}

	public function getDepositMode() {
		return parent::DEPOSIT_MODE_3RDPARTY;
	}

	public function getNoteModel() {
		return parent::NOTE_MODEL_PLATFORM;
	}

	protected function getBankId($direct_pay_extra_info){
		//only wechat
		return 40;
	}

	public function handlePaymentFormResponse($resp, $params) {
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_URL,
			'url' => $resp['break_url'],
		);
	}

	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function configParams(&$params, $direct_pay_extra_info) {}
}
