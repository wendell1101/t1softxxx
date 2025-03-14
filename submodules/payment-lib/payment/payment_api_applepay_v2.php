<?php
require_once dirname(__FILE__) . '/abstract_payment_api_applepay_v2.php';

/**
 * applepay
 *
 * * applepay_PAYMENT_API, ID: 5847
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://mgp-pay.com:8084/
 * * Account: ## Live Merchant no ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 * @copyright 2022 tot (update 2021/06)
 */
class Payment_api_applepay_v2 extends Abstract_payment_api_applepay_v2 {

	public function getPlatformCode() {
		return APPLEPAY_V2_PAYMENT_API;
	}

	public function getPrefix() {
		return 'applepayV2';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['channleType'] = self::PAY_ARG_CHANNELTYPE_DEFAULT;

        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);

            if (!empty($extraInfo)) {
                // $params['bankCode'] = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
                // $params['cardNo'] = array_key_exists('card_number', $extraInfo) ? $extraInfo['card_number'] : null;
            }
        }

    }

	protected function processPaymentUrlForm($params) {
        // return $this->processPaymentUrlFormPost($params);
        return $this->processPaymentUrlFormRedirect($params);
    }

	public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
            // array('name' => 'bank_type', 'type' => 'list', 'label_lang' => 'pay.bank',
            //     'list' => $this->getBankList(), 'list_tree' => $this->getBankListTree()),
            // array('name' => 'card_number', 'type' => 'number', 'label_lang' => 'cashier.player.bank_num'),
        );
    }

}
