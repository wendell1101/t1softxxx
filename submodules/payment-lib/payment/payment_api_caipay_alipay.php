<?php
require_once dirname(__FILE__) . '/abstract_payment_api_caipay.php';

/**
 *
 * * CAIPAY_ALIPAY_PAYMENT_API, ID: 694
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
class Payment_api_caipay_alipay extends Abstract_payment_api_caipay {

	public function getPlatformCode() {
		return CAIPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'caipay_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
	    if($this->utils->is_mobile()){
            $params['accountType'] = '0'; //0-借记卡,1-贷记卡
            $params['tranChannel'] = self::PAY_CHANNEL_WAP;
            $params['prdDesc'] = $params['prdName'];
            $params['pnum'] = '1';
            $params['payMode'] = self::SCANTYPE_ALIPAY_WAP; //00020-银行卡,00025-微信h5,00024-支付宝Wap,00026 支付宝 h5,00033-QQh5,00046-京东wap
        }else{
            $params['payMode'] = self::SCANTYPE_ALIPAY;
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
