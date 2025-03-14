<?php
require_once dirname(__FILE__) . '/abstract_payment_api_vpays.php';

/** 
 *
 * VPAYS  鑫通付 微信
 * 
 * 
 * * 'VPAYS_WEIXIN_PAYMENT_API', ID 890
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: http://www.feifu8.com/Pay_Index.html
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_vpays_weixin extends Abstract_payment_api_vpays {

	public function getPlatformCode() {
		return VPAYS_WEIXIN_PAYMENT_API;
	}

	public function getPrefix() {
		return 'vpays_weixin';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
       
            $params['type'] = self::SCANTYPE_WEIXIN;
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
