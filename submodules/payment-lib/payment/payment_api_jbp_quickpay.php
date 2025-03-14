<?php
require_once dirname(__FILE__) . '/payment_api_daddypay_quickpay.php';

/**
 *
 * DaddyPay Quickpay 银联快捷 JBP 聚宝盆
 *
 * JBP_QUICKPAY_PAYMENT_API, ID: 5000
 *
 * Required Fields:
 * * URL
 * * Key - signing key
 * * Extra Info
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_jbp_quickpay extends Payment_api_daddypay_quickpay {

	public function getPlatformCode() {
		return JBP_QUICKPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'jbp_quickpay';
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
}
