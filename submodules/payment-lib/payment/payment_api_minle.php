<?php
require_once dirname(__FILE__) . '/abstract_payment_api_minle.php';

/** 
 *
 * 民乐
 * 
 * 
 * * MINLE_PAYMENT_API, ID: 485
 *
 * Required Fields:
 * * URL
 * * Account
 * * Key
 *
 * Field Values:
 * * LIVE-URL: 
 * * TEST-URL: http://39.108.10.209/payapi.php/Home/BianJie/obtainPayUrl
 * * Account: ## Merchant ID ##
 * * Key: ## Secret Key ##
 *
 * @category Payment
 * @copyright 2013-2022 tot
 */
class Payment_api_minle extends Abstract_payment_api_minle {

	public function getPlatformCode() {
		return MINLE_PAYMENT_API;
	}

	public function getPrefix() {
		return 'minle';
	}

	protected function configParams(&$params, $direct_pay_extra_info) {
        $this->utils->debug_log('direct_pay_extra_info', $direct_pay_extra_info);
        if (!empty($direct_pay_extra_info)) {
            $extraInfo = json_decode($direct_pay_extra_info, true);
            if (!empty($extraInfo)) {
                $bank = array_key_exists('bank', $extraInfo) ? $extraInfo['bank'] : $extraInfo['bank_type'];
            }
        }

		$params['type'] = self::TYPE_PAY;
        $params['bank_code'] = $this->getBankId($bank);
        $params['card_type'] = '0';

        if($this->utils->is_Mobile()){
            $params['channel'] = self::PAY_CHANNEL_WAP;
        }else{
            $params['channel'] = self::PAY_CHANNEL_PC;
        }

	}

	protected function processPaymentUrlForm($params) {
		return $this->processPaymentUrlFormPost($params);
		
	}
}
