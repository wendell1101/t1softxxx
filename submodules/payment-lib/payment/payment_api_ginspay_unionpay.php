<?php
require_once dirname(__FILE__) . '/abstract_payment_api_ginspay.php';

/** 
 *
 * Ginspay 隱聯支付 銀聯
 * 
 * 
 * * 'GINSPAY_UNIONPAY_PAYMENT_API', ID 945
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
class Payment_api_ginspay_unionpay extends Abstract_payment_api_ginspay {

	public function getPlatformCode() {
		return GINSPAY_UNIONPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'ginspay_unionpay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {

            $params['bank_code'] = self::SCANTYPE_MUP;
        
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
