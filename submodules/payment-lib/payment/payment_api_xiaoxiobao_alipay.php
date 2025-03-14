<?php
require_once dirname(__FILE__) . '/abstract_payment_api_xiaoxiobao.php';

/** 
 *
 * XiaoXioBao 小熊宝
 * 
 * 
 * * 'XIAOXIOBAO_ALIPAY_PAYMENT_API', ID 5063
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * URL: https://api.shaimeixiong.com/api/receive?type=form
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_xiaoxiobao_alipay extends Abstract_payment_api_xiaoxiobao {

	public function getPlatformCode() {
		return XIAOXIOBAO_ALIPAY_PAYMENT_API;
	}

	public function getPrefix() {
		return 'xiaoxiobao_alipay';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {

        $params['paytype'] = self::PAYTYPE_ALIPAY;
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
