<?php
require_once dirname(__FILE__) . '/abstract_payment_api_cpay.php';

/**
 * CPAY
 *
 * * CPAY_WEIXIN_PAYMENT_API, ID: 688
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 * * Extra Info { "cpay_priv_key" }
 *
 * Field Values:
 * * URL: https://api.dobopay.com/v1/api/scanpay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 * * Extra Info: { "cpay_priv_key" : " ## Private Key ## "}
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_cpay_weixin extends Abstract_payment_api_cpay {

	public function getPlatformCode() {
		return CPAY_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'cpay_weixin';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
	    if($this->utils->is_mobile()){
            $params['bankcode'] = self::CHANNELTYPE_WEIXIN_H5;
            $params['channeltype'] = self::CHANNELTYPE_WEIXIN_H5;
            $params['devicetype'] = '2'; //安卓:1；IOS:2；WAP:3
        }else{
            unset($params['pageurl']);
            unset($params['backurl']);
            $params['scantype'] = self::SCANTYPE_WEIXIN;
        }

	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
	    if($this->utils->is_mobile()){
            return $this->processPaymentUrlFormPost($params);
        }else{
            return $this->processPaymentUrlFormQRCode($params);
        }
	}
}
