<?php
require_once dirname(__FILE__) . '/abstract_payment_api_cpay.php';

/**
 * CPAY
 *
 * * CPAY_ALIPAY_PAYMENT_API, ID: 689
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
class Payment_api_cpay_alipay extends Abstract_payment_api_cpay {

	public function getPlatformCode() {
		return CPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'cpay_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        //h5渠道從extra_info控制 , mobile_scan_qrcode = true 的話一律出QRcode
        $scan = ($this->getSystemInfo("mobile_scan_qrcode")) ? $this->getSystemInfo("mobile_scan_qrcode") : false;
        $params['scantype'] = ($this->utils->is_mobile() && ($scan != 'true')) ? self::SCANTYPE_ALIPAY_H5 : self::SCANTYPE_ALIPAY;
        $params['paytype'] = ($this->utils->is_mobile() && ($scan != 'true')) ? self::PAYTYPE_ALIPAY_H5 : self::PAYTYPE_ALIPAY;
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
        $content = $this->process($params);
        $scan = ($this->getSystemInfo("mobile_scan_qrcode")) ? $this->getSystemInfo("mobile_scan_qrcode") : false;
        if($this->utils->is_mobile() && ($scan != 'true')){
            return $this->processPaymentUrlFormPost($content);
        }
        //return $this->processPaymentUrlFormQRCode($content);
        return $this->processPaymentUrlFormPost($content);
	}
}
