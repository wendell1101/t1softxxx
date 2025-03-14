<?php
require_once dirname(__FILE__) . '/abstract_payment_api_xinyitong.php';

/** 
 *
 * xinyitong 新亿通
 * 
 * 
 * * 'XINYITONG_ALIPAY_H5_PAYMENT_API', ID 5079
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://m.xinyitong.com:28080/YFServlet/recvMerchant
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_xinyitong_alipay_h5 extends Abstract_payment_api_xinyitong {

	public function getPlatformCode() {
		return XINYITONG_ALIPAY_H5_PAYMENT_API;
	}

	public function getPrefix() {
		return 'xinyitong_alipay_h5';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
		
        $params['pd_FrpId'] = self::PD_FRPID_ALIPAY_H5;
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
