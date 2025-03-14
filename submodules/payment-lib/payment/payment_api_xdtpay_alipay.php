<?php
require_once dirname(__FILE__) . '/abstract_payment_api_xdtpay.php';
/**
 * XDTPAY 商点通
 *
 * * XDTPAY_ALIPAY_PAYMENT_API, ID: 5313
 * * XDTPAY_ALIPAY_H5_PAYMENT_API, ID: 5314
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.aido88.cn/api_deposit.shtml
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_xdtpay_alipay extends Abstract_payment_api_xdtpay {

	public function getPlatformCode() {
		return XDTPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'xdtpay_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['ChannelPlatform'] = self::CHANNEL_ALIPAY_BANKCARD;
	}

	# Hide bank selection drop-down
    public function getPlayerInputInfo() {
        /*
            1.
            Valid [float_amount_limit] pattern

            pattern: "float_amount_limit": "(A|B|C|D|E|F|...)"

            A: limit amount 1
            B: limit amount 2
            C: limit amount 3

            example: "float_amount_limit": "(1|21|51)"

            2.
            show [float_amount_limit_msg]  when amount is incorrect
        */
        $type = $this->getSystemInfo('float_amount_limit')? 'float_amount_limit' : 'float_amount' ;
        $float_amount_limit_msg = $this->getSystemInfo('float_amount_limit_msg')?$this->getSystemInfo('float_amount_limit_msg'):'請輸入上方金額';
        if($type == 'float_amount_limit'){
            return array(
                array('name' => 'deposit_amount', 'type' => $type, 'label_lang' => 'cashier.09', 'float_amount_limit' => $this->getSystemInfo('float_amount_limit'), 'float_amount_limit_msg' => $float_amount_limit_msg),
            );
        }else{
            return array(
                array('name' => 'deposit_amount', 'type' => $type, 'label_lang' => 'cashier.09'),
            );
        }
    }

	protected function processPaymentUrlForm($params) {
        return $this->processPaymentUrlFormRedirect($params);
	}
}