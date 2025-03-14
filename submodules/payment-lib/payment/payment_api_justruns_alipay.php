<?php
require_once dirname(__FILE__) . '/abstract_payment_api_justruns.php';

/**
 *
 * * JUSTRUNS_ALIPAY_PAYMENT_API, ID: 5669
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://www.justruns3.com/hr/facade/order/merchant/requestOrder
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */

class Payment_api_justruns_alipay extends Abstract_payment_api_justruns {

	public function getPlatformCode() {
		return JUSTRUNS_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'justruns_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['channelCode'] = self::CHANNELCODE_ALIPAY;
	}

	protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormPost($params);
    }

    public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

}
