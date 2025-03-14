<?php
require_once dirname(__FILE__) . '/abstract_payment_api_dpay.php';
/**
 * DPAY / HDBpay鑫多宝
 *
 * * DPAY_ALIPAY_PAYMENT_API, ID: 323
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://api.273787.cn/api/scanpay
 * * Account: ## User ID ##
 * * Key: ## Secret Key ##
 *
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_dpay_alipay extends Abstract_payment_api_dpay {

	public function getPlatformCode() {
		return DPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'dpay_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		if($this->getSystemInfo('useH5')) {
            $params['scantype'] = self::SCANTYPE_ALIPAY_H5;
        }
        else {
            $params['scantype'] = self::SCANTYPE_ALIPAY;
        }
	}

	# Hide bank selection drop-down
	public function getPlayerInputInfo() {
		return array(
			array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
		);
	}

	protected function processPaymentUrlForm($params) {
		if($this->CI->utils->is_mobile() || $this->getSystemInfo('redirect_url', false)) {
            return $this->processPaymentUrlFormURL($params);
        }
        else {
            return $this->processPaymentUrlFormQRCode($params);
        }
	}
}
