<?php
require_once dirname(__FILE__) . '/abstract_payment_api_qpgpay.php';
/**
 * BIFUPAY
 *
 * * BIFUPAY_PAYMENT_API, ID: 5859
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.qpgtrade.com/PaymentGetway/OrderRquest
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_bifupay extends Abstract_payment_api_qpgpay {

	public function getPlatformCode() {
		return BIFUPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'bifupay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['channelType'] = self::DEPOSIT_CHANNEL_BANK;
	}

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        return $this->handlePaymentFormResponse($params);
    }

}