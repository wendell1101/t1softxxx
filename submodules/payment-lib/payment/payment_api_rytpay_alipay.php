<?php
require_once dirname(__FILE__) . '/abstract_payment_api_rytpay.php';

class Payment_api_rytpay_alipay extends Abstract_payment_api_rytpay {
	private $info;
	public function __construct($params = null) {
		parent::__construct($params);
		# Populate $info with the following keys
		# url, key, account, secret, system_info
		$this->info = $this->getInfoByEnv();
	}

	public function getPlatformCode() {
		return RYTPAY_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'rytpay_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		$params['source'] = '1';
	}

	/* hide bar */
	public function getPlayerInputInfo() {
	    return array(
	        array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
	    );
	}	
}
