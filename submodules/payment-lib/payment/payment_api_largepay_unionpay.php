<?php
require_once dirname(__FILE__) . '/abstract_payment_api_largepay.php';

/**
 * LARGEPAY
 *
 * * LARGEPAY_UNIONPAY_PAYMENT_API, ID: 5533
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://pay.hongzhong777.com/gateway/pay.jsp
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_largepay_unionpay extends Abstract_payment_api_largepay {

	public function getPlatformCode() {
		return LARGEPAY_UNIONPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'largepay_unionpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['pay_mode'] = self::PAYMODE_WEBH5;
		$params['bank_code'] = self::BANKCODE_UNIONPAY;
		$params['card_type'] = self::CARD_TYPE;

        if(!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if(!empty($extraInfo['card_number'])){
                $params['bank_card_no'] = $extraInfo['card_number'];
            }
        }
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
            array('name' => 'card_number', 'type' => 'number', 'label_lang' => 'cashier.player.bank_num'),
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}

}
