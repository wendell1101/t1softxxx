<?php
require_once dirname(__FILE__) . '/abstract_payment_api_daddypay.php';

/**
 *
 * DaddyPay Quickpay 银联快捷
 *
 * DADDYPAY_QUICKPAY_PAYMENT_API, ID: 777
 *
 * Required Fields:
 * * URL
 * * Key - signing key
 * * Extra Info
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_daddypay_quickpay extends Abstract_payment_api_daddypay {

	public function getPlatformCode() {
		return DADDYPAY_QUICKPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'daddypay_quickapy';
	}

	public function getDepositMode() {
		return parent::DEPOSIT_MODE_3RDPARTY;
	}

    protected function getBankId($direct_pay_extra_info) {
        return parent::BANKID_QUICKPAY;
    }

	public function getNoteModel() {
		if($this->getSystemInfo("use_note_model_fp")) {
			return parent::NOTE_MODEL_DP;
		}
		return parent::NOTE_MODEL_PLATFORM;
	}

	public function handlePaymentFormResponse($resp, $params) {
		return array(
			'success' => true,
			'type' => self::REDIRECT_TYPE_URL,
			'url' => $resp['break_url'],
		);
	}

    protected function configParams(&$params, $direct_pay_extra_info) {
        $params['terminal'] = $this->utils->is_mobile() ? '2' : '1';
    }

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }
}
