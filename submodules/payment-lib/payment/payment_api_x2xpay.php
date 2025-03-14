<?php
require_once dirname(__FILE__) . '/abstract_payment_api_x2xpay.php';

class Payment_api_x2xpay extends Abstract_payment_api_x2xpay {
	private $info;
	public function __construct($params = null) {
		parent::__construct($params);
		# Populate $info with the following keys
		# url, key, account, secret, system_info
		$this->info = $this->getInfoByEnv();
	}

	public function getPlatformCode() {
		return X2XPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'x2xpay';
	}

	public function getPlayerInputInfo() {
	    return array(
	        array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
	    );
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $params['reqType'] = "h5_pay_request";            // 请求类型 reqType String(64) 是 根据报文类型填写
        $params['channel'] = "01";                        // 支付渠道 channel String(10) 是 01：微信/支付宝H5支付 （此渠道只支持H5支付接口） 0：支付宝 1：微信 2：银联
	}
}
