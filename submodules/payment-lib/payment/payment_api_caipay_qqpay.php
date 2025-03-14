<?php
require_once dirname(__FILE__) . '/abstract_payment_api_caipay.php';

/**
 *
 * * CAIPAY_QQPAY_PAYMENT_API, ID: 695
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: caipay
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_caipay_qqpay extends Abstract_payment_api_caipay {

	public function getPlatformCode() {
		return CAIPAY_QQPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'caipay_qqpay';
	}

    protected function configParams(&$params, $direct_pay_extra_info) {
        if($this->utils->is_mobile()){
            $params['accountType'] = '0'; //0-借记卡,1-贷记卡
            $params['tranChannel'] = self::PAY_CHANNEL_WAP;
            $params['prdDesc'] = $params['prdName'];
            $params['pnum'] = '1';
            $params['payMode'] = self::SCANTYPE_QQPAY_H5;
        }else{
            $params['payMode'] = self::SCANTYPE_QQPAY;
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
