<?php
require_once dirname(__FILE__) . '/abstract_payment_api_zhifu.php';

/**
 * ZHIFU 知付
 *
 * * ZHIFU_ALIPAY_PAYMENT_API, ID: 943
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
class Payment_api_zhifu_alipay extends Abstract_payment_api_zhifu {

	public function getPlatformCode() {
		return ZHIFU_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'zhifu_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['Mode'] = ($this->CI->utils->is_mobile()) ? self::MODE_ALIPAY_H5 : self::MODE_ALIPAY;
        $params['BankCode'] = self::BANKCODE_ALIPAY;
	}

    public function getPlayerInputInfo() {
        return array(
            array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

    protected function processPaymentUrlForm($params) {
        if($this->CI->utils->is_mobile()) {
            return $this->processPaymentUrlFormRedirect($params);
        }else{
            return $this->processPaymentUrlFormQRCode($params);
        }
    }
}
