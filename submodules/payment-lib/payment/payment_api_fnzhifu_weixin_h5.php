<?php
require_once dirname(__FILE__) . '/abstract_payment_api_fnzhifu.php';

/**
* FNZHIFU  蜂鸟
 *
 * * 'FNZHIFU_WEIXIN_H5_PAYMENT_API', ID 5294
 *
 * Required Fields:
 * * Account
 * * Extra Info
 *
 *
 * Field Values:
 * * URL: https://api.fnzhifu.com/
 * * Extra Info:
 * * {
 * *    "fnzhifu_server_pub_key":
 * * }
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_fnzhifu_weixin_h5 extends Abstract_payment_api_fnzhifu {

	public function getPlatformCode() {
		return FNZHIFU_WEIXIN_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'fnzhifu_weixin_h5';
	}


	protected function configParams(&$params, $direct_pay_extra_info) {
	}

    
	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
	}


	public function getPlayerInputInfo() {
        
        return array(
             array('name' => 'deposit_amount', 'type' => 'float_amount', 'label_lang' => 'cashier.09'),
        );
    }

}
