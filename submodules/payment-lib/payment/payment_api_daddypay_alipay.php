<?php
require_once dirname(__FILE__) . '/abstract_payment_api_daddypay.php';

/**
 *
 * DaddyPay alipay 支付寶
 *
 * DADDYPAY_ALIPAY_PAYMENT_API, ID: 132
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
class Payment_api_daddypay_alipay extends Abstract_payment_api_daddypay {

	public function getPlatformCode() {
		return DADDYPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'daddypay_alipay';
	}

	public function getDepositMode() {
		return parent::DEPOSIT_MODE_ALIPAY;
	}

	public function getNoteModel() {
		return parent::NOTE_MODEL_DP;
	}

	protected function getBankId($direct_pay_extra_info){
		//only alipay
		return 30;
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

	public function getNote($playerId, $direct_pay_extra_info){
		$playerDetails = $this->getPlayerDetails($playerId);

		$firstname = (isset($playerDetails[0]) && !empty($playerDetails[0]['firstName'])) ? $playerDetails[0]['firstName'] : '';
		$lastname =  (isset($playerDetails[0]) && !empty($playerDetails[0]['lastName']))  ? $playerDetails[0]['lastName']  : '';

		return $lastname.$firstname;
	}

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['terminal'] = $this->utils->is_mobile()? '2' : '1';
    }
}
