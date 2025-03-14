<?php
require_once dirname(__FILE__) . '/abstract_payment_api_metropay.php';

/**
 * MetroPay
 * https://metro-pay.com
 *
 * METROPAY_PAYMENT_API, ID: 5964
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * SFTP: /infos/C024_OLE777thb/Payments/MetroPay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2022 tot
 */
class Payment_api_metropay extends Abstract_payment_api_metropay {

	public function getPlatformCode() {
		return METROPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'metropay';
	}

	public function getChannelId() {
		return parent::CHANNEL_BANK;
	}

    public function getPlayerInputInfo() {
        return [
            [
                'name' => 'deposit_amount', 
                'type' => 'float_amount', 
                'label_lang' => 'cashier.09'
            ]
        ];
    }

    protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }
}
